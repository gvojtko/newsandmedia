<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use BadMethodCallException;
use App\Component\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractExportChangedCronModule implements SimpleCronModuleInterface
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
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        AbstractIndex $index,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->index = $index;
        $this->indexFacade = $indexFacade;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
    }

    /**
     * @required
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @internal This function will be replaced by constructor injection in next major
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        if ($this->eventDispatcher !== null && $this->eventDispatcher !== $eventDispatcher) {
            throw new BadMethodCallException(
                sprintf('Method "%s" has been already called and cannot be called multiple times.', __METHOD__)
            );
        }
        if ($this->eventDispatcher !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );
        $this->eventDispatcher = $eventDispatcher;
    }

    public function run()
    {
        $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($this->index::getName());
        $this->indexFacade->exportChanged($this->index, $indexDefinition, new NullOutput());

        $this->eventDispatcher->dispatch(new IndexExportedEvent($this->index), IndexExportedEvent::INDEX_EXPORTED);
    }
}
