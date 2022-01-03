<?php

namespace App\Model\Product;

use App\Component\EntityExtension\EntityNameResolver;
use App\Model\Category\Category;

class ProductCategoryFactory implements ProductCategoryFactoryInterface
{
    /**
     * @var \App\Component\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \App\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\ProductCategory
     */
    public function create(
        Product $product,
        Category $category
    ): ProductCategory {
        $classData = $this->entityNameResolver->resolve(ProductCategory::class);

        return new $classData($product, $category);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category[] $categories
     * @return \App\Model\Product\ProductCategory[]
     */
    public function createMultiple(
        Product $product,
        array $categories
    ): array {
        $productCategoryDomains = [];
        foreach ($categories as $category) {
            $productCategory[] = $this->create(
                $product,
                $category
            );
        }

        return $productCategoryDomains;
    }
}
