<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Component\Elasticsearch\AbstractIndex;
use App\Component\Elasticsearch\IndexSupportChangesOnlyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductIndex extends AbstractIndex implements IndexSupportChangesOnlyInterface
{
    /**
     * @var \App\Model\Product\Elasticsearch\ProductExportRepository
     */
    protected $productExportRepository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param \App\Model\Product\Elasticsearch\ProductExportRepository $productExportRepository
     */
    public function __construct(
        ProductExportRepository $productExportRepository,
        ContainerInterface $container
    ) {
        $this->productExportRepository = $productExportRepository;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        return $this->productExportRepository->getProductTotalCount();
    }

    /**
     * @inheritDoc
     */
    public function getChangedCount(): int
    {
        return $this->productExportRepository->getProductChangedCount();
    }

    /**
     * @inheritDoc
     */
    public function getChangedIdsForBatch(int $lastProcessedId, int $batchSize): array
    {
        return $this->productExportRepository->getProductIdsForChanged($lastProcessedId, $batchSize);
    }

    /**
     * @inheritDoc
     */
    public function getExportDataForIds(array $restrictToIds): array
    {
        return $this->productExportRepository->getProductsDataForIds(
            $this->container->getParameter('locale'),
            $restrictToIds
        );
    }

    /**
     * @inheritDoc
     */
    public function getExportDataForBatch(int $lastProcessedId, int $batchSize): array
    {
        return $this->productExportRepository->getProductsData(
            $this->container->getParameter('locale'),
            $lastProcessedId,
            $batchSize
        );
    }

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'Product';
    }
}
