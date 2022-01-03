<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var \App\Component\Elasticsearch\AbstractExportScheduler
     */
    protected $exportScheduler;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \App\Component\Elasticsearch\IndexFacade
     */
    protected $indexFacade;

    /**
     * @var \App\Component\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @var \App\Component\Elasticsearch\AbstractIndex
     */
    protected $index;

    /**
     * @param \App\Component\Elasticsearch\AbstractExportScheduler $exportScheduler
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     */
    public function __construct(
        AbstractExportScheduler $exportScheduler,
        EntityManagerInterface $entityManager,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader,
        AbstractIndex $index
    ) {
        $this->exportScheduler = $exportScheduler;
        $this->entityManager = $entityManager;
        $this->indexFacade = $indexFacade;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
        $this->index = $index;
    }

    /**
     * @inheritDoc
     */
    abstract public static function getSubscribedEvents(): array;

    public function exportScheduledRows(): void
    {
        if ($this->exportScheduler->hasAnyRowIdsForImmediateExport()) {
            // to be sure the recalculated data are fetched from database properly
            $this->entityManager->clear();

            $productIds = $this->exportScheduler->getRowIdsForImmediateExport();

            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition(
                $this->index::getName(),
            );

            $this->indexFacade->exportIds($this->index, $indexDefinition, $productIds);
        }
    }
}
