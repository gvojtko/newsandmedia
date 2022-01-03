<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Model\Category\CategoryFacade;
use BadMethodCallException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use App\Component\Paginator\QueryPaginator;
use App\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use App\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;

class ProductExportRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlRepository
     */
    protected $friendlyUrlRepository;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    protected $friendlyUrlFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    protected $categoryFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductFacade $productFacade,
        FriendlyUrlRepository $friendlyUrlRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        CategoryFacade $categoryFacade
    ) {
        $this->productFacade = $productFacade;
        $this->em = $em;
        $this->friendlyUrlRepository = $friendlyUrlRepository;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @required
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @internal This function will be replaced by constructor injection in next major
     */
    public function setCategoryFacade(CategoryFacade $categoryFacade): void
    {
        if (
            $this->categoryFacade !== null
            && $this->categoryFacade !== $categoryFacade
        ) {
            throw new BadMethodCallException(sprintf(
                'Method "%s" has been already called and cannot be called multiple times.',
                __METHOD__
            ));
        }
        if ($this->categoryFacade !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param string $locale
     * @param int $lastProcessedId
     * @param int $batchSize
     * @return array
     */
    public function getProductsData(string $locale, int $lastProcessedId, int $batchSize): array
    {
        $queryBuilder = $this->createQueryBuilder()
            ->andWhere('l.id > :lastProcessedId')
            ->setParameter('lastProcessedId', $lastProcessedId)
            ->setMaxResults($batchSize);

        $query = $queryBuilder->getQuery();

        $results = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $results[$product->getId()] = $this->extractResult($product, $locale);
        }

        return $results;
    }

    /**
     * @param string $locale
     * @param int[] $productIds
     * @return array
     */
    public function getProductsDataForIds(string $locale, array $productIds): array
    {
        $queryBuilder = $this->createQueryBuilder()
            ->andWhere('l.id IN (:ProductIds)')
            ->setParameter('ProductIds', $productIds);

        $query = $queryBuilder->getQuery();

        $result = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $result[$product->getId()] = $this->extractResult($product, $locale);
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getProductTotalCount(): int
    {
        $result = new QueryPaginator($this->createQueryBuilder());

        return $result->getTotalCount();
    }

    /**
     * @param int $lastProcessedId
     * @param int $batchSize
     * @return int[]
     */
    public function getProductIdsForChanged(int $lastProcessedId, int $batchSize): array
    {
        $result = $this->em->createQueryBuilder()
            ->select('l.id')
            ->from(Product::class, 'l')
            ->where('l.exportProduct = TRUE')
            ->andWhere('l.id > :lastProcessedId')
            ->orderBy('l.id')
            ->setParameter('lastProcessedId', $lastProcessedId)
            ->setMaxResults($batchSize)
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'id');
    }

    /**
     * @return int
     */
    public function getProductChangedCount(): int
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('l.id')
            ->from(Product::class, 'l')
            ->where('l.exportProduct = TRUE');

        $result = new QueryPaginator($queryBuilder);

        return $result->getTotalCount();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return array
     */
    protected function extractResult(Product $product, string $locale): array
    {
        $categoryIds = $this->extractCategories($product);
        $parameters = $this->extractParameters($locale, $product);
        $prices = $this->extractPrices($product);

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'categories' => $categoryIds,
            'main_category_id' => $this->categoryFacade->getProductMainCategory(
                $product,
            )->getId(),
            'prices' => $prices,
            'parameters' => $parameters,
            'ordering_priority' => $product->getOrderingPriority(),
            'uuid' => $product->getUuid(),
            'accessories' => $this->extractAccessoriesIds($product),
        ];
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int[]
     */
    protected function extractVariantIds(Product $product): array
    {
        $variantIds = [];

        foreach ($product->getVariants() as $variant) {
            $variantIds[] = $variant->getId();
        }

        return $variantIds;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string
     */
    protected function extractDetailUrl(Product $product): string
    {
        $friendlyUrl = $this->friendlyUrlRepository->getMainFriendlyUrl(
            'front_Product_detail',
            $product->getId()
        );

        return $this->friendlyUrlFacade->getAbsoluteUrlByFriendlyUrl($friendlyUrl);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Product::class, 'l')
            //->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.Product = l.id')
             //   ->andWhere('prv.domainId = :domainId')
             //   ->andWhere('prv.visible = TRUE')
            ->groupBy('l.id')
            ->orderBy('l.id');

        //$queryBuilder->setParameter('domainId', $domainId);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int[]
     */
    protected function extractCategories(Product $product): array
    {
        return $product->getCategoriesIds();
    }

    /**
     * @param string $locale
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractParameters(string $locale, Product $product): array
    {
        $parameters = [];
        $productParameterValues = $this->parameterRepository->getProductParameterValuesByProductSortedByName(
            $product,
            $locale
        );
        foreach ($productParameterValues as $productParameterValue) {
            $parameter = $productParameterValue->getParameter();
            $parameterValue = $productParameterValue->getValue();
            if ($parameter->getName($locale) === null || $parameterValue->getLocale() !== $locale) {
                continue;
            }

            $parameters[] = [
                'parameter_id' => $parameter->getId(),
                'parameter_uuid' => $parameter->getUuid(),
                'parameter_name' => $parameter->getName($locale),
                'parameter_value_id' => $parameterValue->getId(),
                'parameter_value_uuid' => $parameterValue->getUuid(),
                'parameter_value_text' => $parameterValue->getText(),
            ];
        }

        return $parameters;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractPrices(Product $product): array
    {
        $prices = [];
        /** @var \App\Model\Product\Pricing\ProductSellingPrice[] $productSellingPrices */
        $productSellingPrices = $this->productFacade->getAllProductSellingPrices($product);
        foreach ($productSellingPrices as $productSellingPrice) {
            $sellingPrice = $productSellingPrice->getSellingPrice();
            $priceFrom = false;
            if ($sellingPrice instanceof ProductPrice) {
                $priceFrom = $sellingPrice->isPriceFrom();
            }

            $prices[] = [
                'pricing_group_id' => $productSellingPrice->getPricingGroup()->getId(),
                'price_with_vat' => (float)$sellingPrice->getPriceWithVat()->getAmount(),
                'price_without_vat' => (float)$sellingPrice->getPriceWithoutVat()->getAmount(),
                'vat' => (float)$sellingPrice->getVatAmount()->getAmount(),
                'price_from' => $priceFrom,
            ];
        }

        return $prices;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string
     */
    protected function getBrandUrlForDomainByProduct(Product $product): string
    {
        $brand = $product->getBrand();
        if ($brand === null) {
            return '';
        }

        return $this->brandCachedFacade->getBrandUrl($brand->getId());
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractAccessoriesIds(Product $product): array
    {
        $accessoriesIds = [];
        $accessories = $this->productAccessoryFacade->getAllAccessories($product);

        foreach ($accessories as $accessory) {
            $accessoriesIds[] = $accessory->getAccessory()->getId();
        }

        return $accessoriesIds;
    }
}
