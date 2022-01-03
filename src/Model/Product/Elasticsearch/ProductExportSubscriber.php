<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use Doctrine\ORM\EntityManagerInterface;
use App\Component\Elasticsearch\AbstractExportSubscriber;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Elasticsearch\IndexFacade;
use Symfony\Component\HttpKernel\KernelEvents;

class ProductExportSubscriber extends AbstractExportSubscriber
{
    /**
     * @param \App\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \App\Model\Product\Elasticsearch\ProductIndex $index
     */
    public function __construct(
        ProductExportScheduler $productExportScheduler,
        EntityManagerInterface $entityManager,
        IndexFacade $indexFacade,
        IndexDefinitionLoader $indexDefinitionLoader,
        ProductIndex $index
    ) {
        parent::__construct(
            $productExportScheduler,
            $entityManager,
            $indexFacade,
            $indexDefinitionLoader,
            $index
        );
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['exportScheduledRows', -30],
            ],
        ];
    }
}
