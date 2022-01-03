<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Doctrine\ORM\QueryBuilder;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Brand\Brand;
use App\Model\Product\ProductRepository;

class BrandFilterChoiceRepository
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandFilterChoicesInCategory(PricingGroup $pricingGroup, Category $category)
    {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        return $this->getBrandsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string|null $searchText
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandFilterChoicesForSearch(PricingGroup $pricingGroup, $searchText)
    {
        $productsQueryBuilder = $this->productRepository
            ->getListableBySearchTextQueryBuilder($pricingGroup, $searchText);

        return $this->getBrandsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandFilterChoicesForAll(PricingGroup $pricingGroup): array
    {
        $productsQueryBuilder = $this->productRepository
            ->getAllListableQueryBuilder($pricingGroup);

        return $this->getBrandsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @return \App\Model\Product\Brand\Brand[]
     */
    protected function getBrandsByProductsQueryBuilder(QueryBuilder $productsQueryBuilder)
    {
        $clonedProductsQueryBuilder = clone $productsQueryBuilder;

        $clonedProductsQueryBuilder
            ->select('1')
            ->join('p.brand', 'pb')
            ->andWhere('pb.id = b.id')
            ->resetDQLPart('orderBy');

        $brandsQueryBuilder = $productsQueryBuilder->getEntityManager()->createQueryBuilder();
        $brandsQueryBuilder
            ->select('b')
            ->from(Brand::class, 'b')
            ->andWhere($brandsQueryBuilder->expr()->exists($clonedProductsQueryBuilder))
            ->orderBy('b.name', 'asc');

        foreach ($clonedProductsQueryBuilder->getParameters() as $parameter) {
            $brandsQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        return $brandsQueryBuilder->getQuery()->execute();
    }
}
