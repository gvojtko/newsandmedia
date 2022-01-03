<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Component\Elasticsearch\AbstractExportCronModule;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Elasticsearch\IndexFacade;

class ProductExportCronModule extends AbstractExportCronModule
{
    /**
     * @param \App\Model\Product\Elasticsearch\ProductIndex $index
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        ProductIndex $index,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        parent::__construct($index, $indexFacade, $indexDefinitionLoader);
    }
}
