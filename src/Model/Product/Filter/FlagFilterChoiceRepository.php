<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Flag\Flag;
use App\Model\Product\ProductRepository;

class FlagFilterChoiceRepository
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
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagFilterChoicesInCategory(PricingGroup $pricingGroup, Category $category)
    {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        return $this->getVisibleFlagsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagFilterChoicesForBrand(PricingGroup $pricingGroup, Brand $brand): array
    {
        $productsQueryBuilder = $this->productRepository->getListableForBrandQueryBuilderPublic(
            $pricingGroup,
            $brand
        );

        return $this->getVisibleFlagsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagFilterChoicesForAll(PricingGroup $pricingGroup): array
    {
        $productsQueryBuilder = $this->productRepository->getAllListableQueryBuilder(
            $pricingGroup
        );

        return $this->getVisibleFlagsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string|null $searchText
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagFilterChoicesForSearch(PricingGroup $pricingGroup, $searchText)
    {
        $productsQueryBuilder = $this->productRepository
            ->getListableBySearchTextQueryBuilder($pricingGroup, $searchText);

        return $this->getVisibleFlagsByProductsQueryBuilder($productsQueryBuilder);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @return \App\Model\Product\Flag\Flag[]
     */
    protected function getVisibleFlagsByProductsQueryBuilder(QueryBuilder $productsQueryBuilder)
    {
        $clonedProductsQueryBuilder = clone $productsQueryBuilder;

        $clonedProductsQueryBuilder
            ->select('1')
            ->join('p.flags', 'pf')
            ->andWhere('pf.id = f.id')
            ->andWhere('f.visible = true')
            ->resetDQLPart('orderBy');

        $flagsQueryBuilder = $productsQueryBuilder->getEntityManager()->createQueryBuilder();
        $flagsQueryBuilder
            ->select('f, ft')
            ->from(Flag::class, 'f')
            ->andWhere($flagsQueryBuilder->expr()->exists($clonedProductsQueryBuilder))
            ->orderBy('f.name', 'asc');

        foreach ($clonedProductsQueryBuilder->getParameters() as $parameter) {
            $flagsQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        return $flagsQueryBuilder->getQuery()->execute();
    }
}
