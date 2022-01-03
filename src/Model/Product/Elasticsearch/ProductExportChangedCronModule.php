<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Component\Elasticsearch\AbstractExportChangedCronModule;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Elasticsearch\IndexFacade;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductExportChangedCronModule extends AbstractExportChangedCronModule
{
    /**
     * @param \App\Model\Product\Elasticsearch\ProductIndex $index
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        ProductIndex $index,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($index, $indexFacade, $indexDefinitionLoader, $eventDispatcher);
    }
}
