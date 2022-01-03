<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use App\Component\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\NullOutput;

abstract class AbstractExportCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \App\Component\Elasticsearch\AbstractIndex
     */
    protected $index;

    /**
     * @var \App\Component\Elasticsearch\IndexFacade
     */
    protected $indexFacade;

    /**
     * @var \App\Component\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        AbstractIndex $index,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        $this->index = $index;
        $this->indexFacade = $indexFacade;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($this->index::getName());
        $this->indexFacade->export($this->index, $indexDefinition, new NullOutput());
    }
}
