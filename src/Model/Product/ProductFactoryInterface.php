<?php

namespace App\Model\Product;

interface ProductFactoryInterface
{
    /**
     * @param \App\Model\Product\ProductData $data
     * @return \App\Model\Product\Product
     */
    public function create(ProductData $data): Product;

    /**
     * @param \App\Model\Product\ProductData $data
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Product\Product
     */
    public function createMainVariant(ProductData $data, Product $mainProduct, array $variants): Product;
}
