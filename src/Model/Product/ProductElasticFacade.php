<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Domain\Domain;
use App\Component\Elasticsearch\IndexDefinitionLoader;
use App\Component\Paginator\PaginationResult;
use App\Model\Category\Category;
use App\Model\Customer\User\CurrentCustomerUser;
use App\Model\Product\Accessory\ProductAccessoryRepository;
use App\Model\Product\Filter\ProductFilterConfig;
use App\Model\Product\Filter\ProductFilterCountData;
use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Listing\ProductListOrderingConfig;
use App\Model\Product\Search\FilterQuery;
use App\Model\Product\Search\FilterQueryFactory;
use App\Model\Product\Search\ProductElasticsearchRepository;
use App\Model\Product\Search\ProductFilterCountDataElasticsearchRepository;
use App\Model\Product\Search\ProductFilterDataToQueryTransformer;

class ProductElasticFacade implements ProductFacadeInterface
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \App\Model\Product\Accessory\ProductAccessoryRepository
     */
    protected $productAccessoryRepository;

    /**
     * @var \App\Model\Product\Search\ProductElasticsearchRepository
     */
    protected $productElasticsearchRepository;

    /**
     * @var \App\Model\Product\Search\ProductFilterCountDataElasticsearchRepository
     */
    protected $productFilterCountDataElasticsearchRepository;

    /**
     * @var \App\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @var \App\Model\Product\Search\FilterQueryFactory
     */
    protected $filterQueryFactory;

    /**
     * @var \App\Component\Elasticsearch\IndexDefinitionLoader
     */
    protected $indexDefinitionLoader;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
     * @param \App\Model\Product\Search\ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository
     * @param \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     * @param \App\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductElasticsearchRepository $productElasticsearchRepository,
        ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository,
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        FilterQueryFactory $filterQueryFactory,
        IndexDefinitionLoader $indexDefinitionLoader
    ) {
        $this->productRepository = $productRepository;
        $this->productElasticsearchRepository = $productElasticsearchRepository;
        $this->productFilterCountDataElasticsearchRepository = $productFilterCountDataElasticsearchRepository;
        $this->productFilterDataToQueryTransformer = $productFilterDataToQueryTransformer;
        $this->filterQueryFactory = $filterQueryFactory;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleProductById(int $productId): Product
    {
        return $this->productRepository->getVisible(
            $productId,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessoriesForProduct(Product $product): array
    {
        return $this->productAccessoryRepository->getAllOfferedAccessoriesByProduct(
            $product,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVariantsForProduct(Product $product): array
    {
        return $this->productRepository->getAllSellableVariantsByMainVariant(
            $product,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsInCategory(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $categoryId
    ): PaginationResult {
        $filterQuery = $this->filterQueryFactory->createListableProductsByCategoryId(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit,
            $categoryId
        );

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForBrand(
        string $orderingModeId,
        int $page,
        int $limit,
        int $brandId
    ): PaginationResult {
        $emptyProductFilterData = new ProductFilterData();

        $filterQuery = $this->filterQueryFactory->createListableProductsByBrandId(
            $emptyProductFilterData,
            $orderingModeId,
            $page,
            $limit,
            $brandId
        );

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForSearch(
        string $searchText,
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit
    ): PaginationResult {
        $filterQuery = $this->filterQueryFactory->createListableProductsBySearchText(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit,
            $searchText
        );

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);

        return new PaginationResult($page, $limit, $productsResult->getTotal(), $productsResult->getHits());
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchAutocompleteProducts(?string $searchText, int $limit): PaginationResult
    {
        $searchText = $searchText ?? '';

        $emptyProductFilterData = new ProductFilterData();
        $page = 1;

        $filterQuery = $this->filterQueryFactory->createListableProductsBySearchText(
            $emptyProductFilterData,
            ProductListOrderingConfig::ORDER_BY_RELEVANCE,
            $page,
            $limit,
            $searchText
        );

        $productIds = $this->productElasticsearchRepository->getSortedProductIdsByFilterQuery($filterQuery);

        $listableProductsByIds = $this->productRepository->getListableByIds(
            $this->currentCustomerUser->getPricingGroup(),
            $productIds->getIds()
        );

        return new PaginationResult($page, $limit, $productIds->getTotal(), $listableProductsByIds);
    }

    /**
     * {@inheritdoc}
     */
    public function getProductFilterCountDataInCategory(
        int $categoryId,
        ProductFilterConfig $productFilterConfig,
        ProductFilterData $productFilterData
    ): ProductFilterCountData {
        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInCategory(
            $productFilterData,
            $this->filterQueryFactory->createListableProductsByCategoryIdWithPriceAndStockFilter(
                $categoryId,
                $productFilterData
            )
        );
    }

    /**
     * @param int $brandId
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataForBrand(
        int $brandId,
        ProductFilterData $productFilterData
    ): ProductFilterCountData {
        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInCategory(
            $productFilterData,
            $this->filterQueryFactory->createListableProductsByBrandIdWithPriceAndStockFilter(
                $brandId,
                $productFilterData
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProductFilterCountDataForSearch(
        ?string $searchText,
        ProductFilterConfig $productFilterConfig,
        ProductFilterData $productFilterData
    ): ProductFilterCountData {
        $searchText = $searchText ?? '';

        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInSearch(
            $productFilterData,
            $this->filterQueryFactory->createListableProductsBySearchTextWithPriceAndStockFilter(
                $searchText,
                $productFilterData
            )
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataForAll(
        ProductFilterData $productFilterData
    ): ProductFilterCountData {
        return $this->productFilterCountDataElasticsearchRepository->getProductFilterCountDataInSearch(
            $productFilterData,
            $this->filterQueryFactory->createListableProductsWithPriceAndStockFilter($productFilterData)
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $categoryId
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsInCategoryFilterQuery(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $categoryId
    ): FilterQuery {
        return $this->filterQueryFactory->createListableProductsByCategoryId(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit,
            $categoryId
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $brandId
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsForBrandFilterQuery(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $brandId
    ): FilterQuery {
        return $this->filterQueryFactory->createListableProductsByBrandId(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit,
            $brandId
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param string|null $searchText
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createListableProductsForSearchTextFilterQuery(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        ?string $searchText
    ): FilterQuery {
        $searchText = $searchText ?? '';

        return $this->filterQueryFactory->createListableProductsBySearchText(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit,
            $searchText
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \App\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit
    ): FilterQuery {
        return $this->filterQueryFactory->createWithProductFilterData(
            $productFilterData,
            $orderingModeId,
            $page,
            $limit
        );
    }

    /**
     * @return string
     */
    protected function getIndexName(): string
    {
        return $this->filterQueryFactory->getIndexName();
    }

    /**
     * @return int
     */
    public function getProductsCount(): int
    {
        $filterQuery = $this->filterQueryFactory->createListable();

        return $this->productElasticsearchRepository->getProductsCountByFilterQuery($filterQuery);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProducts(int $limit, int $offset, string $orderingModeId): array
    {
        $emptyProductFilterData = new ProductFilterData();
        $filterQuery = $this->filterQueryFactory->createWithProductFilterData(
            $emptyProductFilterData,
            $orderingModeId,
            1,
            $limit
        )->setFrom($offset);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);
        return $productsResult->getHits();
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProductsByCategory(Category $category, int $limit, int $offset, string $orderingModeId): array
    {
        $emptyProductFilterData = new ProductFilterData();
        $filterQuery = $this->filterQueryFactory->createListableProductsByCategoryId(
            $emptyProductFilterData,
            $orderingModeId,
            1,
            $limit,
            $category->getId()
        )->setFrom($offset);

        $productsResult = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery);
        return $productsResult->getHits();
    }
}
