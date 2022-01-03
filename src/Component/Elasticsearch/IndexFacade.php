<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use Doctrine\ORM\EntityManagerInterface;
use App\Component\Console\ProgressBarFactory;
use App\Component\Doctrine\SqlLoggerFacade;
use App\Component\Elasticsearch\Exception\ElasticsearchNoAliasException;
use Symfony\Component\Console\Output\OutputInterface;

class IndexFacade
{
    /**
     * @var \App\Component\Elasticsearch\IndexRepository
     */
    protected $indexRepository;

    /**
     * @var \App\Component\Console\ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * @var \App\Component\Doctrine\SqlLoggerFacade
     */
    protected $sqlLoggerFacade;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param \App\Component\Elasticsearch\IndexRepository $indexRepository
     * @param \App\Component\Console\ProgressBarFactory $progressBarFactory
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(
        IndexRepository $indexRepository,
        ProgressBarFactory $progressBarFactory,
        SqlLoggerFacade $sqlLoggerFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->indexRepository = $indexRepository;
        $this->progressBarFactory = $progressBarFactory;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function create(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $output->writeln(sprintf(
            'Creating index "%s"',
            $indexDefinition->getIndexName()
        ));

        $this->indexRepository->createIndex($indexDefinition);
        $this->indexRepository->createAlias($indexDefinition);
    }

    /**
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function delete(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $output->writeln(sprintf(
            'Deleting index "%s"',
            $indexDefinition->getIndexName(),
        ));

        $this->indexRepository->deleteIndexByIndexDefinition($indexDefinition);
    }

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function export(AbstractIndex $index, IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $output->writeln(sprintf(
            'Exporting data of "%s"',
            $indexDefinition->getIndexName(),
        ));

        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $indexAlias = $indexDefinition->getIndexAlias();
        $progressBar = $this->progressBarFactory->create(
            $output,
            $index->getTotalCount()
        );

        $exportedIds = [];
        $lastProcessedId = 0;
        do {
            // detach objects from manager to prevent memory leaks
            $this->entityManager->clear();
            $currentBatchData = $index->getExportDataForBatch(
                $lastProcessedId,
                $index->getExportBatchSize()
            );
            $currentBatchSize = count($currentBatchData);

            if ($currentBatchSize === 0) {
                break;
            }

            $this->indexRepository->bulkUpdate($indexAlias, $currentBatchData);
            $progressBar->advance($currentBatchSize);

            $exportedIds = array_merge($exportedIds, array_keys($currentBatchData));
            $lastProcessedId = array_key_last($currentBatchData);
        } while ($currentBatchSize >= $index->getExportBatchSize());

        $this->indexRepository->deleteNotPresent($indexDefinition, $exportedIds);

        $progressBar->finish();
        $output->writeln('');

        $this->sqlLoggerFacade->reenableLogging();
    }

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function exportChanged(AbstractIndex $index, IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        if (!$index instanceof IndexSupportChangesOnlyInterface) {
            $output->writeln(
                sprintf(
                    'Index "%s" does not support export of only changed rows. Skipping.',
                    $indexDefinition->getIndexName()
                )
            );

            return;
        }

        $output->writeln(sprintf(
            'Exporting changed data of "%s"',
            $indexDefinition->getIndexName(),
        ));

        $progressBar = $this->progressBarFactory->create(
            $output,
            $index->getChangedCount()
        );

        $lastProcessedId = 0;
        while (($changedIdsBatch = $index->getChangedIdsForBatch(
            $lastProcessedId,
            $index->getExportBatchSize()
        )) !== []) {
            $this->exportIds($index, $indexDefinition, $changedIdsBatch);

            $progressBar->advance(count($changedIdsBatch));
            $lastProcessedId = end($changedIdsBatch);
        }

        $progressBar->finish();
        $output->writeln('');
    }

    /**
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function migrate(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $indexName = $indexDefinition->getIndexName();

        try {
            $existingIndexName = $this->indexRepository->findCurrentIndexNameForAlias(
                $indexDefinition->getIndexAlias()
            );
        } catch (ElasticsearchNoAliasException $exception) {
            $existingIndexName = $this->indexRepository->findCurrentIndexNameForAlias(
                $indexDefinition->getLegacyIndexAlias()
            );
        }

        if ($existingIndexName === $indexDefinition->getVersionedIndexName()) {
            $output->writeln(sprintf('Index "%s" is up to date', $indexName));
            return;
        }

        $output->writeln(sprintf('Migrating index "%s"', $indexName));
        $this->indexRepository->createIndex($indexDefinition);
        $this->indexRepository->reindex($existingIndexName, $indexDefinition->getVersionedIndexName());
        $this->indexRepository->createAlias($indexDefinition);
        $this->indexRepository->deleteIndex($existingIndexName);
    }

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param int[] $restrictToIds
     */
    public function exportIds(AbstractIndex $index, IndexDefinition $indexDefinition, array $restrictToIds): void
    {
        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $indexAlias = $indexDefinition->getIndexAlias();

        $chunkedIdsToExport = array_chunk($restrictToIds, $index->getExportBatchSize());

        foreach ($chunkedIdsToExport as $idsToExport) {
            // detach objects from manager to prevent memory leaks
            $this->entityManager->clear();
            $currentBatchData = $index->getExportDataForIds($idsToExport);

            if (count($currentBatchData) > 0) {
                $this->indexRepository->bulkUpdate($indexAlias, $currentBatchData);
            }

            $idsToDelete = array_values(array_diff($idsToExport, array_keys($currentBatchData)));
            if (count($idsToDelete) > 0) {
                $this->indexRepository->deleteIds($indexAlias, $idsToDelete);
            }
        }

        $this->sqlLoggerFacade->reenableLogging();
    }
}
