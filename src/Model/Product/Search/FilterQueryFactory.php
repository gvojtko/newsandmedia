<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Component\Domain\Domain;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Model\Customer\User\CurrentCustomerUser;
use App\Model\Product\Elasticsearch\ProductIndex;
use App\Model\Product\Filter\ProductFilterData;

class FilterQueryFactory
{
    /**
     * @var \App\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @var \App\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \App\Component\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @param \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        $this->productFilterDataToQueryTransformer = $productFilterDataToQueryTransformer;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
    }

    /**
     * @param string $indexName
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function create(string $indexName): FilterQuery
    {
        return new FilterQuery($indexName);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $categoryId
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsByCategoryId(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $categoryId
    ): FilterQuery {
        return $this->createWithProductFilterData($productFilterData, $orderingModeId, $page, $limit)
            ->filterByCategory([$categoryId]);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createWithProductFilterData(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit
    ): FilterQuery {
        return $this->createListableWithProductFilter($productFilterData)
            ->setPage($page)
            ->setLimit($limit)
            ->applyOrdering($orderingModeId, $this->currentCustomerUser->getPricingGroup());
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $brandId
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsByBrandId(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $brandId
    ): FilterQuery {
        return $this->createWithProductFilterData($productFilterData, $orderingModeId, $page, $limit)
            ->filterByBrands([$brandId]);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param string $searchText
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsBySearchText(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        string $searchText
    ): FilterQuery {
        return $this->createWithProductFilterData($productFilterData, $orderingModeId, $page, $limit)
            ->search($searchText);
    }

    /**
     * @return string
     * @internal visibility of this method will be changed to protected in next major version
     */
    public function getIndexName(): string
    {
        return $this->indexDefinitionLoader->getIndexDefinition(
            ProductIndex::getName()
        )->getIndexAlias();
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListable(): FilterQuery
    {
        return $this->createVisible()
            ->filterOnlySellable()
            ->filterOutVariants();
    }

    /**
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createVisible(): FilterQuery
    {
        return $this->create($this->getIndexName())
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup());
    }

    /**
     * @param int $categoryId
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsByCategoryIdWithPriceAndStockFilter(int $categoryId, ProductFilterData $productFilterData): FilterQuery
    {
        $filterQuery = $this->createListable()
            ->filterByCategory([$categoryId]);
        $filterQuery = $this->addPricesAndStockFromFilterDataToQuery($productFilterData, $filterQuery);

        return $filterQuery;
    }

    /**
     * @param int $brandId
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsByBrandIdWithPriceAndStockFilter(int $brandId, ProductFilterData $productFilterData): FilterQuery
    {
        $filterQuery = $this->createListable()
            ->filterByBrands([$brandId]);
        $filterQuery = $this->addPricesAndStockFromFilterDataToQuery($productFilterData, $filterQuery);

        return $filterQuery;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsWithPriceAndStockFilter(ProductFilterData $productFilterData): FilterQuery
    {
        $filterQuery = $this->createListable();
        $filterQuery = $this->addPricesAndStockFromFilterDataToQuery($productFilterData, $filterQuery);

        return $filterQuery;
    }

    /**
     * @param string $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableProductsBySearchTextWithPriceAndStockFilter(string $searchText, ProductFilterData $productFilterData): FilterQuery
    {
        $filterQuery = $this->createListable()
            ->search($searchText);
        $filterQuery = $this->addPricesAndStockFromFilterDataToQuery($productFilterData, $filterQuery);

        return $filterQuery;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $filterQuery
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function addPricesAndStockFromFilterDataToQuery(ProductFilterData $productFilterData, FilterQuery $filterQuery): FilterQuery
    {
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery(
            $productFilterData,
            $filterQuery,
            $this->currentCustomerUser->getPricingGroup()
        );
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);

        return $filterQuery;
    }

    /**
     * @param int[] $productIds
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createVisibleProductsByProductIdsFilter(array $productIds): FilterQuery
    {
        return $this->createVisible()
            ->filterByProductIds($productIds);
    }

    /**
     * @param int[] $productIds
     * @param int|null $limit
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createSellableProductsByProductIdsFilter(array $productIds, ?int $limit = null): FilterQuery
    {
        $filterQuery = $this
            ->createVisibleProductsByProductIdsFilter($productIds)
            ->filterOnlySellable()
            ->applyOrderingByIdsArray($productIds);

        if ($limit === null) {
            return $filterQuery;
        }

        return $filterQuery->setLimit($limit);
    }

    /**
     * @param string[] $productUuids
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createVisibleProductsByProductUuidsFilter(array $productUuids): FilterQuery
    {
        return $this->createVisible()
            ->filterByProductUuids($productUuids);
    }

    /**
     * @param string[] $productUuids
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createSellableProductsByProductUuidsFilter(array $productUuids): FilterQuery
    {
        return $this->createVisibleProductsByProductUuidsFilter($productUuids)
            ->filterOnlySellable();
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Search\FilterQuery
     */
    public function createListableWithProductFilter(ProductFilterData $productFilterData): FilterQuery
    {
        $filterQuery = $this->createListable();
        $filterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery(
            $productFilterData,
            $filterQuery
        );
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery(
            $productFilterData,
            $filterQuery,
            $this->currentCustomerUser->getPricingGroup()
        );

        return $filterQuery;
    }
}
