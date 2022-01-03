<?php

declare(strict_types=1);

namespace App\Command\Elasticsearch;

use App\Component\Elasticsearch\AbstractIndex;
use App\Component\Elasticsearch\IndexDefinition;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Elasticsearch\IndexExportedEvent;
use App\Component\Elasticsearch\IndexFacade;
use App\Component\Elasticsearch\IndexRegistry;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ElasticsearchDataExportCommand extends AbstractElasticsearchIndexCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:elasticsearch:data-export';

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param \App\Component\Elasticsearch\IndexRegistry $indexRegistry
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function __construct(IndexRegistry $indexRegistry, IndexFacade $indexFacade, IndexDefinitionLoader $indexDefinitionLoader, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($indexRegistry, $indexFacade, $indexDefinitionLoader);

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    protected function executeForIndex(OutputInterface $output, AbstractIndex $index): void
    {
        parent::executeForIndex($output, $index);

        $this->eventDispatcher->dispatch(
            new IndexExportedEvent($index),
        );

//        $this->eventDispatcher->dispatch(
//            new IndexExportedEvent($index),
//            IndexExportedEvent::INDEX_EXPORTED
//        );
    }

    /**
     * @inheritDoc
     */
    protected function executeCommand(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $this->indexFacade->export(
            $this->indexRegistry->getIndexByIndexName($indexDefinition->getIndexName()),
            $indexDefinition,
            $output
        );
    }

    /**
     * @inheritDoc
     */
    protected function getCommandDescription(): string
    {
        return 'Export data to Elasticsearch';
    }

    /**
     * @inheritDoc
     */
    protected function getArgumentNameDescription(): string
    {
        return sprintf(
            'Which index data should be exported? Available indexes: "%s"',
            implode(', ', $this->indexRegistry->getRegisteredIndexNames())
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActionStartedMessage(): string
    {
        return 'Exporting data';
    }

    /**
     * @inheritDoc
     */
    protected function getActionFinishedMessage(): string
    {
        return 'Data was exported successfully!';
    }
}
