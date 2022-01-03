<?php

declare(strict_types=1);

namespace App\Command\Elasticsearch;

use App\Component\Elasticsearch\IndexDefinition;
use Symfony\Component\Console\Output\OutputInterface;

class ElasticsearchIndexesCreateCommand extends AbstractElasticsearchIndexCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:elasticsearch:indexes-create';

    /**
     * @inheritDoc
     */
    protected function executeCommand(IndexDefinition $indexDefinition, OutputInterface $output): void
    {
        $this->indexFacade->create($indexDefinition, $output);
    }

    /**
     * @inheritDoc
     */
    protected function getCommandDescription(): string
    {
        return 'Creates indexes in Elasticsearch';
    }

    /**
     * @inheritDoc
     */
    protected function getArgumentNameDescription(): string
    {
        return sprintf(
            'Which index should be created? Available indexes: "%s"',
            implode(', ', $this->indexRegistry->getRegisteredIndexNames())
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActionStartedMessage(): string
    {
        return 'Creating indexes';
    }

    /**
     * @inheritDoc
     */
    protected function getActionFinishedMessage(): string
    {
        return 'Indexes created successfully!';
    }
}
