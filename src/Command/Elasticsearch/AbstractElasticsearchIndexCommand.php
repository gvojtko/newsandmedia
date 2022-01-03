<?php

declare(strict_types=1);

namespace App\Command\Elasticsearch;

use App\Command\CommandResultCodes;
use App\Component\Elasticsearch\AbstractIndex;
use App\Component\Elasticsearch\IndexDefinition;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Elasticsearch\IndexFacade;
use App\Component\Elasticsearch\IndexRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractElasticsearchIndexCommand extends Command
{
    private const ARGUMENT_INDEX_NAME = 'name';

    /**
     * @var \App\Component\Elasticsearch\IndexRegistry
     */
    protected $indexRegistry;

    /**
     * @var \App\Component\Elasticsearch\IndexFacade
     */
    protected $indexFacade;

    /**
     * @var \App\Component\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @param \App\Component\Elasticsearch\IndexRegistry $indexRegistry
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        IndexRegistry $indexRegistry,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        $this->indexRegistry = $indexRegistry;
        $this->indexFacade = $indexFacade;
        $this->indexDefinitionLoader = $indexDefinitionLoader;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument(
                self::ARGUMENT_INDEX_NAME,
                InputArgument::OPTIONAL,
                $this->getArgumentNameDescription()
            )
            ->setDescription($this->getCommandDescription());
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyleIo = new SymfonyStyle($input, $output);
        $indexName = $input->getArgument(self::ARGUMENT_INDEX_NAME);
        $output->writeln($this->getActionStartedMessage());

        foreach ($this->getAffectedIndexes($indexName) as $index) {
            $this->executeForIndex($output, $index);
        }

        $symfonyStyleIo->success($this->getActionFinishedMessage());

        return CommandResultCodes::RESULT_OK;
    }

    /**
     * @param string|null $indexName
     * @return \App\Component\Elasticsearch\AbstractIndex[]
     */
    private function getAffectedIndexes(?string $indexName): array
    {
        if ($indexName) {
            return [$this->indexRegistry->getIndexByIndexName($indexName)];
        }
        return $this->indexRegistry->getRegisteredIndexes();
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     */
    protected function executeForIndex(OutputInterface $output, AbstractIndex $index): void
    {
        $this->executeCommand(
            $this->indexDefinitionLoader->getIndexDefinition($index::getName()),
            $output
        );
    }

    /**
     * @param \App\Component\Elasticsearch\IndexDefinition $indexDefinition
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    abstract protected function executeCommand(IndexDefinition $indexDefinition, OutputInterface $output): void;

    /**
     * @return string
     */
    abstract protected function getCommandDescription(): string;

    /**
     * @return string
     */
    abstract protected function getArgumentNameDescription(): string;

    /**
     * @return string
     */
    abstract protected function getActionStartedMessage(): string;

    /**
     * @return string
     */
    abstract protected function getActionFinishedMessage(): string;
}
