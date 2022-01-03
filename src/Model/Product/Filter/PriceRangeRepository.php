<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Doctrine\ORM\QueryBuilder;
use App\Component\Doctrine\QueryBuilderExtender;
use App\Component\Money\Money;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Pricing\ProductCalculatedPrice;
use App\Model\Product\ProductRepository;

class PriceRangeRepository
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Component\Doctrine\QueryBuilderExtender
     */
    protected $queryBuilderExtender;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender
     */
    public function __construct(ProductRepository $productRepository, QueryBuilderExtender $queryBuilderExtender)
    {
        $this->productRepository = $productRepository;
        $this->queryBuilderExtender = $queryBuilderExtender;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeInCategory(PricingGroup $pricingGroup, Category $category)
    {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \App\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeForBrand(PricingGroup $pricingGroup, Brand $brand): PriceRange
    {
        $productsQueryBuilder = $this->productRepository->getListableForBrandQueryBuilderPublic(
            $pricingGroup,
            $brand
        );

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeForAll(PricingGroup $pricingGroup): PriceRange
    {
        $productsQueryBuilder = $this->productRepository->getAllListableQueryBuilder(
            $pricingGroup
        );

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string|null $searchText
     * @return \App\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeForSearch(PricingGroup $pricingGroup, $searchText)
    {
        $productsQueryBuilder = $this->productRepository
            ->getListableBySearchTextQueryBuilder($pricingGroup, $searchText);

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Filter\PriceRange
     */
    protected function getPriceRangeByProductsQueryBuilder(QueryBuilder $productsQueryBuilder, PricingGroup $pricingGroup)
    {
        $queryBuilder = clone $productsQueryBuilder;

        $this->queryBuilderExtender
            ->addOrExtendJoin($queryBuilder, ProductCalculatedPrice::class, 'pcp', 'pcp.product = p')
            ->andWhere('pcp.pricingGroup = :pricingGroup')
            ->setParameter('pricingGroup', $pricingGroup)
            ->resetDQLPart('groupBy')
            ->resetDQLPart('orderBy')
            ->select('MIN(pcp.priceWithVat) AS minimalPrice, MAX(pcp.priceWithVat) AS maximalPrice');

        $priceRangeData = $queryBuilder->getQuery()->execute();
        $priceRangeDataRow = reset($priceRangeData);

        return new PriceRange(
            Money::create($priceRangeDataRow['minimalPrice'] ?? 0),
            Money::create($priceRangeDataRow['maximalPrice'] ?? 0)
        );
    }
}
