<?php

declare(strict_types=1);

namespace App\Command\Elasticsearch;

use App\Component\Elasticsearch\IndexDefinition;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticsearchChangedDataExportCommand extends ElasticsearchDataExportCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:elasticsearch:changed-data-export';

    /**
     * @inheritDoc
     */
    protected function executeCommand(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $this->indexFacade->exportChanged(
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
        return 'Export changed data to Elasticsearch';
    }

    /**
     * @inheritDoc
     */
    protected function getActionStartedMessage(): string
    {
        return 'Exporting changed data';
    }

    /**
     * @inheritDoc
     */
    protected function getActionFinishedMessage(): string
    {
        return 'Changed data was exported successfully!';
    }
}
