<?php

namespace App\Model\Product;

use App\Component\EntityExtension\EntityNameResolver;

class ProductFactory implements ProductFactoryInterface
{
    /**
     * @var \App\Component\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \App\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(
        EntityNameResolver $entityNameResolver
    ) {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param \App\Model\Product\ProductData $data
     * @return \App\Model\Product\Product
     */
    public function create(ProductData $data): Product
    {
        $classData = $this->entityNameResolver->resolve(Product::class);

        $product = $classData::create($data);

        return $product;
    }

    /**
     * @param \App\Model\Product\ProductData $data
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Product\Product
     */
    public function createMainVariant(ProductData $data, Product $mainProduct, array $variants): Product
    {
        $variants[] = $mainProduct;

        $classData = $this->entityNameResolver->resolve(Product::class);

        $mainVariant = $classData::createMainVariant($data, $variants);

        return $mainVariant;
    }
}
