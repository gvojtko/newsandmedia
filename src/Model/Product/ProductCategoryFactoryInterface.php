<?php

namespace App\Model\Product;

use App\Model\Category\Category;

interface ProductCategoryFactoryInterface
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\ProductCategory
     */
    public function create(
        Product $product,
        Category $category
    ): ProductCategory;

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category[] $categories
     * @return \App\Model\Product\ProductCategory[]
     */
    public function createMultiple(
        Product $product,
        array $categories
    ): array;
}
