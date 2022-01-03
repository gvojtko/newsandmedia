<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Paginator\PaginationResult;
use App\Model\Category\Category;
use App\Model\Product\Filter\ProductFilterConfig;
use App\Model\Product\Filter\ProductFilterCountData;
use App\Model\Product\Filter\ProductFilterData;

interface ProductFacadeInterface
{
    /**
     * @param int $productId
     * @return \App\Model\Product\Product
     */
    public function getVisibleProductById(int $productId): Product;

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getAccessoriesForProduct(Product $product): array;

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForProduct(Product $product): array;

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $categoryId
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsInCategory(
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit,
        int $categoryId
    ): PaginationResult;

    /**
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @param int $brandId
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsForBrand(
        string $orderingModeId,
        int $page,
        int $limit,
        int $brandId
    ): PaginationResult;

    /**
     * @param string $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginatedProductsForSearch(
        string $searchText,
        ProductFilterData $productFilterData,
        string $orderingModeId,
        int $page,
        int $limit
    ): PaginationResult;

    /**
     * @param string|null $searchText
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getSearchAutocompleteProducts(?string $searchText, int $limit): PaginationResult;

    /**
     * @param int $categoryId
     * @param \App\Model\Product\Filter\ProductFilterConfig $productFilterConfig
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInCategory(
        int $categoryId,
        ProductFilterConfig $productFilterConfig,
        ProductFilterData $productFilterData
    ): ProductFilterCountData;

    /**
     * @param string|null $searchText
     * @param \App\Model\Product\Filter\ProductFilterConfig $productFilterConfig
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataForSearch(
        ?string $searchText,
        ProductFilterConfig $productFilterConfig,
        ProductFilterData $productFilterData
    ): ProductFilterCountData;

    /**
     * @param \App\Model\Category\Category $category
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProductsByCategory(Category $category, int $limit, int $offset, string $orderingModeId): array;

    /**
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProducts(int $limit, int $offset, string $orderingModeId): array;

    /**
     * @return int
     */
    public function getProductsCount(): int;
}
