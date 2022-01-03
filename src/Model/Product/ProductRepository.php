<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use App\Component\Doctrine\QueryBuilderExtender;
use App\Component\Paginator\PaginationResult;
use App\Component\Paginator\QueryPaginator;
use App\Model\Category\Category;
use App\Model\Localization\Localization;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Availability\Availability;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Exception\InvalidOrderingModeException;
use App\Model\Product\Exception\ProductNotFoundException;
use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Filter\ProductFilterRepository;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Listing\ProductListOrderingConfig;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ProductParameterValue;
use App\Model\Product\Pricing\ProductCalculatedPrice;
use App\Model\Product\Search\ProductElasticsearchRepository;
use App\Model\Product\Unit\Unit;

class ProductRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Product\Filter\ProductFilterRepository
     */
    protected $productFilterRepository;

    /**
     * @var \App\Component\Doctrine\QueryBuilderExtender
     */
    protected $queryBuilderExtender;

    /**
     * @var \App\Model\Localization\Localization
     */
    protected $localization;

    /**
     * @var \App\Model\Product\Search\ProductElasticsearchRepository
     */
    protected $productElasticsearchRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Filter\ProductFilterRepository $productFilterRepository
     * @param \App\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender
     * @param \App\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductFilterRepository $productFilterRepository,
        QueryBuilderExtender $queryBuilderExtender,
        ProductElasticsearchRepository $productElasticsearchRepository
    ) {
        $this->em = $em;
        $this->productFilterRepository = $productFilterRepository;
        $this->queryBuilderExtender = $queryBuilderExtender;
        $this->productElasticsearchRepository = $productElasticsearchRepository;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductRepository()
    {
        return $this->em->getRepository(Product::class);
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product|null
     */
    public function findById($id)
    {
        return $this->getProductRepository()->find($id);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllListableQueryBuilder(PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->getAllOfferedQueryBuilder($pricingGroup);
        $queryBuilder->andWhere('p.variantType != :variantTypeVariant')
            ->setParameter('variantTypeVariant', Product::VARIANT_TYPE_VARIANT);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllSellableQueryBuilder(PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->getAllOfferedQueryBuilder($pricingGroup);
        $queryBuilder->andWhere('p.variantType != :variantTypeMain')
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllOfferedQueryBuilder(PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($pricingGroup);
        $queryBuilder->andWhere('p.calculatedSellingDenied = FALSE');

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllVisibleQueryBuilder(PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
                ->where('prv.pricingGroup = :pricingGroup')
                ->andWhere('prv.visible = TRUE')
            ->orderBy('p.id');

        $queryBuilder->setParameter('pricingGroup', $pricingGroup);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getListableInCategoryQueryBuilder(
        PricingGroup $pricingGroup,
        Category $category
    ) {
        $queryBuilder = $this->getAllListableQueryBuilder($pricingGroup);
        $this->filterByCategory($queryBuilder, $category);
        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getListableForBrandQueryBuilder(
        PricingGroup $pricingGroup,
        Brand $brand
    ) {
        $queryBuilder = $this->getAllListableQueryBuilder($pricingGroup);
        $this->filterByBrand($queryBuilder, $brand);
        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getListableForBrandQueryBuilderPublic(
        PricingGroup $pricingGroup,
        Brand $brand
    ) {
        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. It will be replaced by getListableForBrandQueryBuilder() which will change its visibility to public.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );
        return $this->getListableForBrandQueryBuilder($pricingGroup, $brand);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSellableInCategoryQueryBuilder(
        PricingGroup $pricingGroup,
        Category $category
    ) {
        $queryBuilder = $this->getAllSellableQueryBuilder($pricingGroup);
        $this->filterByCategory($queryBuilder, $category);
        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOfferedInCategoryQueryBuilder(
        PricingGroup $pricingGroup,
        Category $category
    ) {
        $queryBuilder = $this->getAllOfferedQueryBuilder($pricingGroup);
        $this->filterByCategory($queryBuilder, $category);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string|null $searchText
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getListableBySearchTextQueryBuilder(
        PricingGroup $pricingGroup,
        $searchText
    ) {
        $queryBuilder = $this->getAllListableQueryBuilder($pricingGroup);

        $this->productElasticsearchRepository->filterBySearchText($queryBuilder, $searchText);

        return $queryBuilder;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param \App\Model\Category\Category $category
     */
    protected function filterByCategory(QueryBuilder $queryBuilder, Category $category)
    {
        $queryBuilder->join(
            'p.productCategory',
            'pc',
            Join::WITH,
            'pc.category = :category'
        );
        $queryBuilder->setParameter('category', $category);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param \App\Model\Product\Brand\Brand $brand
     */
    protected function filterByBrand(QueryBuilder $queryBuilder, Brand $brand)
    {
        $queryBuilder->andWhere('p.brand = :brand');
        $queryBuilder->setParameter('brand', $brand);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $page
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForListableInCategory(
        Category $category,
        ProductFilterData $productFilterData,
        $orderingModeId,
        PricingGroup $pricingGroup,
        $page,
        $limit
    ) {
        $queryBuilder = $this->getFilteredListableInCategoryQueryBuilder(
            $category,
            $productFilterData,
            $pricingGroup
        );

        $this->applyOrdering($queryBuilder, $orderingModeId, $pricingGroup);

        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }

    /**
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllListableTranslatedAndOrderedQueryBuilder(
        string $orderingModeId,
        PricingGroup $pricingGroup
    ): QueryBuilder {
        $queryBuilder = $this->getAllListableQueryBuilder(
            $pricingGroup
        );

        $this->applyOrdering($queryBuilder, $orderingModeId, $pricingGroup);

        return $queryBuilder;
    }

    /**
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllListableTranslatedAndOrderedQueryBuilderByCategory(
        string $orderingModeId,
        PricingGroup $pricingGroup,
        Category $category
    ): QueryBuilder {
        $queryBuilder = $this->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        $this->applyOrdering($queryBuilder, $orderingModeId, $pricingGroup);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Product\Brand\Brand $brand
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $page
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForListableForBrand(
        Brand $brand,
        $orderingModeId,
        PricingGroup $pricingGroup,
        $page,
        $limit
    ) {
        $queryBuilder = $this->getListableForBrandQueryBuilder(
            $pricingGroup,
            $brand
        );

        $this->applyOrdering($queryBuilder, $orderingModeId, $pricingGroup);

        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getFilteredListableInCategoryQueryBuilder(
        Category $category,
        ProductFilterData $productFilterData,
        PricingGroup $pricingGroup
    ) {
        $queryBuilder = $this->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        $this->productFilterRepository->applyFiltering(
            $queryBuilder,
            $productFilterData,
            $pricingGroup
        );

        return $queryBuilder;
    }

    /**
     * @param string|null $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $page
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForSearchListable(
        $searchText,
        ProductFilterData $productFilterData,
        $orderingModeId,
        PricingGroup $pricingGroup,
        $page,
        $limit
    ) {
        $queryBuilder = $this->getFilteredListableForSearchQueryBuilder(
            $searchText,
            $productFilterData,
            $pricingGroup
        );

        $this->productElasticsearchRepository->addRelevance($queryBuilder, $searchText);
        $this->applyOrdering($queryBuilder, $orderingModeId, $pricingGroup);

        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }

    /**
     * @param string|null $searchText
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getFilteredListableForSearchQueryBuilder(
        $searchText,
        ProductFilterData $productFilterData,
        PricingGroup $pricingGroup
    ) {
        $queryBuilder = $this->getListableBySearchTextQueryBuilder(
            $pricingGroup,
            $searchText
        );

        $this->productFilterRepository->applyFiltering(
            $queryBuilder,
            $productFilterData,
            $pricingGroup
        );

        return $queryBuilder;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $orderingModeId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     */
    protected function applyOrdering(
        QueryBuilder $queryBuilder,
        $orderingModeId,
        PricingGroup $pricingGroup
    ) {
        switch ($orderingModeId) {
            case ProductListOrderingConfig::ORDER_BY_NAME_ASC:
                $collation = $this->getCollation();
                $queryBuilder->orderBy("COLLATE(pt.name, '" . $collation . "')", 'asc');
                break;

            case ProductListOrderingConfig::ORDER_BY_NAME_DESC:
                $collation = $this->getCollation();
                $queryBuilder->orderBy("COLLATE(pt.name, '" . $collation . "')", 'desc');
                break;

            case ProductListOrderingConfig::ORDER_BY_PRICE_ASC:
                $this->queryBuilderExtender->addOrExtendJoin(
                    $queryBuilder,
                    ProductCalculatedPrice::class,
                    'pcp',
                    'pcp.product = p AND pcp.pricingGroup = :pricingGroup'
                );
                $queryBuilder->orderBy('pcp.priceWithVat', 'asc');
                $queryBuilder->setParameter('pricingGroup', $pricingGroup);
                break;

            case ProductListOrderingConfig::ORDER_BY_PRICE_DESC:
                $this->queryBuilderExtender->addOrExtendJoin(
                    $queryBuilder,
                    ProductCalculatedPrice::class,
                    'pcp',
                    'pcp.product = p AND pcp.pricingGroup = :pricingGroup'
                );
                $queryBuilder->orderBy('pcp.priceWithVat', 'desc');
                $queryBuilder->setParameter('pricingGroup', $pricingGroup);
                break;

            case ProductListOrderingConfig::ORDER_BY_RELEVANCE:
                $queryBuilder->orderBy('relevance', 'asc');
                break;

            case ProductListOrderingConfig::ORDER_BY_PRIORITY:
                $queryBuilder->orderBy('p.orderingPriority', 'desc');
                $collation = $this->getCollation();
                $queryBuilder->addOrderBy("COLLATE(pt.name, '" . $collation . "')", 'asc');
                break;

            default:
                $message = 'Product list ordering mode "' . $orderingModeId . '" is not supported.';
                throw new InvalidOrderingModeException($message);
        }

        $queryBuilder->addOrderBy('p.id', 'asc');
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product
     */
    public function getById($id)
    {
        $product = $this->findById($id);

        if ($product === null) {
            throw new ProductNotFoundException('Product with ID ' . $id . ' does not exist.');
        }

        return $product;
    }

    /**
     * @param int[] $ids
     * @return \App\Model\Product\Product[]
     */
    public function getAllByIds($ids)
    {
        return $this->getProductRepository()->findBy(['id' => $ids]);
    }

    /**
     * @param int $id
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product
     */
    public function getVisible($id, PricingGroup $pricingGroup)
    {
        $qb = $this->getAllVisibleQueryBuilder($pricingGroup);
        $qb->andWhere('p.id = :productId');
        $qb->setParameter('productId', $id);

        $product = $qb->getQuery()->getOneOrNullResult();

        if ($product === null) {
            throw new ProductNotFoundException();
        }

        return $product;
    }

    /**
     * @param int $id
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product
     */
    public function getSellableById($id, PricingGroup $pricingGroup)
    {
        $qb = $this->getAllSellableQueryBuilder($pricingGroup);
        $qb->andWhere('p.id = :productId');
        $qb->setParameter('productId', $id);

        $product = $qb->getQuery()->getOneOrNullResult();

        if ($product === null) {
            throw new ProductNotFoundException();
        }

        return $product;
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][]
     */
    public function getProductIteratorForReplaceVat()
    {
        $query = $this->em->createQuery('
            SELECT DISTINCT p
            FROM ' . Product::class . ' p
            JOIN p.vat v
            WHERE v.replaceWith IS NOT NULL
        ');

        return $query->iterate();
    }

    public function markAllProductsForAvailabilityRecalculation()
    {
        $this->em
            ->createQuery('UPDATE ' . Product::class . ' p SET p.recalculateAvailability = TRUE
                WHERE p.recalculateAvailability = FALSE')
            ->execute();
    }

    public function markAllProductsForPriceRecalculation()
    {
        // Performance optimization:
        // Main variant price recalculation is triggered by variants visibility recalculation
        // and visibility recalculation is triggered by variant price recalculation.
        // Therefore main variant price recalculation is useless here.
        $this->em
            ->createQuery('UPDATE ' . Product::class . ' p SET p.recalculatePrice = TRUE
                WHERE p.variantType != :variantTypeMain AND p.recalculateAvailability = FALSE')
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN)
            ->execute();
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][]
     */
    public function getProductsForPriceRecalculationIterator()
    {
        return $this->getProductRepository()
            ->createQueryBuilder('p')
            ->where('p.recalculatePrice = TRUE')
            ->getQuery()
            ->iterate();
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][]
     */
    public function getProductsForAvailabilityRecalculationIterator()
    {
        return $this->getProductRepository()
            ->createQueryBuilder('p')
            ->where('p.recalculateAvailability = TRUE')
            ->getQuery()
            ->iterate();
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllSellableVariantsByMainVariant(Product $mainVariant, PricingGroup $pricingGroup)
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant = :mainVariant')
            ->setParameter('mainVariant', $mainVariant);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllSellableUsingStockInStockQueryBuilder($pricingGroup)
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($pricingGroup);
        $queryBuilder
            ->andWhere('p.usingStock = TRUE')
            ->andWhere('p.stockQuantity > 0');

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     * @return \App\Model\Product\Product[]
     */
    public function getAtLeastSomewhereSellableVariantsByMainVariant(Product $mainVariant)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->andWhere('p.calculatedVisibility = TRUE')
            ->andWhere('p.calculatedSellingDenied = FALSE')
            ->andWhere('p.variantType = :variantTypeVariant')->setParameter(
                'variantTypeVariant',
                Product::VARIANT_TYPE_VARIANT
            )
            ->andWhere('p.mainVariant = :mainVariant')->setParameter('mainVariant', $mainVariant);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int[] $sortedProductIds
     * @return \App\Model\Product\Product[]
     */
    public function getOfferedByIds(PricingGroup $pricingGroup, array $sortedProductIds)
    {
        if (count($sortedProductIds) === 0) {
            return [];
        }

        $queryBuilder = $this->getAllOfferedQueryBuilder($pricingGroup);
        $queryBuilder
            ->andWhere('p.id IN (:productIds)')
            ->setParameter('productIds', $sortedProductIds)
            ->addSelect('field(p.id, ' . implode(',', $sortedProductIds) . ') AS HIDDEN relevance')
            ->orderBy('relevance');

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int[] $sortedProductIds
     * @return \App\Model\Product\Product[]
     */
    public function getListableByIds(PricingGroup $pricingGroup, array $sortedProductIds): array
    {
        if (count($sortedProductIds) === 0) {
            return [];
        }

        $queryBuilder = $this->getAllListableQueryBuilder($pricingGroup);
        $queryBuilder
            ->andWhere('p.id IN (:productIds)')
            ->setParameter('productIds', $sortedProductIds)
            ->addSelect('field(p.id, ' . implode(',', $sortedProductIds) . ') AS HIDDEN relevance')
            ->orderBy('relevance');

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $productCatnum
     * @return \App\Model\Product\Product
     */
    public function getOneByCatnumExcludeMainVariants($productCatnum)
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p')
            ->andWhere('p.catnum = :catnum')
            ->andWhere('p.variantType != :variantTypeMain')
            ->setParameter('catnum', $productCatnum)
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN);
        $product = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($product === null) {
            throw new ProductNotFoundException(
                'Product with catnum ' . $productCatnum . ' does not exist.'
            );
        }

        return $product;
    }

    /**
     * @param string $uuid
     * @return \App\Model\Product\Product
     */
    public function getOneByUuid(string $uuid): Product
    {
        $product = $this->getProductRepository()->findOneBy(['uuid' => $uuid]);

        if ($product === null) {
            throw new ProductNotFoundException('Product with UUID ' . $uuid . ' does not exist.');
        }

        return $product;
    }

    /**
     * @param \App\Model\Product\ProductQueryParams $query
     * @return \App\Component\Paginator\PaginationResult
     */
    public function findByProductQueryParams(ProductQueryParams $query): PaginationResult
    {
        $queryBuilder = $this->getProductRepository()->createQueryBuilder('p');
        $queryBuilder->orderBy('p.id');
        if ($query->getUuids()) {
            $queryBuilder->andWhere('p.uuid IN (:uuids)');
            $queryBuilder->setParameter(':uuids', $query->getUuids());
        }

        $queryPaginator = new QueryPaginator($queryBuilder);
        return $queryPaginator->getResult($query->getPage(), $query->getPageSize());
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return array
     */
    public function getAllOfferedProducts(PricingGroup $pricingGroup): array
    {
        return $this->getAllOfferedQueryBuilder($pricingGroup)->getQuery()->execute();
    }

    /**
     * @param \App\Model\Product\Product[] $products
     */
    public function markProductsForExport(array $products): void
    {
        $this->em->createQuery('UPDATE ' . Product::class . ' p SET p.exportProduct = TRUE WHERE p IN (:products)')
            ->setParameter('products', $products)
            ->execute();
    }

    public function markAllProductsForExport(): void
    {
        $this->em->createQuery('UPDATE ' . Product::class . ' p SET p.exportProduct = TRUE')
            ->execute();
    }

    public function markAllProductsAsExported(): void
    {
        $this->em->createQuery('UPDATE ' . Product::class . ' p SET p.exportProduct = FALSE')
            ->execute();
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return array
     */
    public function getProductsWithParameter(Parameter $parameter): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->innerJoin(ProductParameterValue::class, 'ppv', 'WITH', 'ppv.product = p')
            ->where('ppv.parameter = :parameter')
            ->setParameter('parameter', $parameter)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Product\Availability\Availability $availability
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithAvailability(Availability $availability): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.calculatedAvailability = :availability')
            ->setParameter('availability', $availability)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Product\Brand\Brand $brand
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithBrand(Brand $brand): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.brand = :brand')
            ->setParameter('brand', $brand)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Product\Flag\Flag $flag
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithFlag(Flag $flag): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->leftJoin('p.flags', 'f')
            ->where('f.id = :flag')
            ->setParameter('flag', $flag)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Product\Unit\Unit $unit
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithUnit(Unit $unit): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->where('p.unit = :unit')
            ->setParameter('unit', $unit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string
     */
    public function getCollation(): string
    {
        return 'en-x-icu';
    }
}
