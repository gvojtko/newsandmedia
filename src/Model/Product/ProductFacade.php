<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Elasticsearch\ProductExportScheduler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use App\Component\Doctrine\SortableNullsWalker;
use App\Component\Paginator\PaginationResult;
use App\Model\Category\Category;
use App\Model\Category\CategoryRepository;
use App\Model\Customer\User\CurrentCustomerUser;
use App\Model\Product\Accessory\ProductAccessoryRepository;
use App\Model\Product\Brand\BrandRepository;
use App\Model\Product\Filter\ProductFilterConfig;
use App\Model\Product\Filter\ProductFilterCountData;
use App\Model\Product\Filter\ProductFilterCountRepository;
use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Listing\ProductListOrderingConfig;

class ProductFacade implements ProductFacadeInterface
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \App\Model\Category\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \App\Model\Product\Filter\ProductFilterCountRepository
     */
    protected $productFilterCountRepository;

    /**
     * @var \App\Model\Product\Elasticsearch\ProductExportScheduler
     */
    protected $productExportScheduler;

    /**
     * @var \App\Model\Product\ProductFactoryInterface
     */
    protected $productFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     * @param \App\Model\Product\Filter\ProductFilterCountRepository $productFilterCountRepository
     * @param \App\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     * @param \App\Model\Product\ProductFactoryInterface $productFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProductFilterCountRepository $productFilterCountRepository,
        ProductExportScheduler $productExportScheduler,
        ProductFactoryInterface $productFactory
    ) {
        $this->em = $em;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productFilterCountRepository = $productFilterCountRepository;
        $this->productExportScheduler = $productExportScheduler;
        $this->productFactory = $productFactory;
    }

    /**
     * @param int $productId
     * @return \App\Model\Product\Product
     */
    public function getById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * @param \App\Model\Product\ProductQueryParams $query
     * @return \App\Component\Paginator\PaginationResult
     */
    public function findByProductQueryParams(ProductQueryParams $query): PaginationResult
    {
        return $this->productRepository->findByProductQueryParams($query);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public function create(ProductData $productData)
    {
        $product = $this->productFactory->create($productData);

        $this->em->persist($product);
        $this->em->flush($product);
        $this->setAdditionalDataAfterCreate($product, $productData);

        $this->productExportScheduler->scheduleRowIdForImmediateExport($product->getId());

        return $product;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\ProductData $productData
     */
    public function setAdditionalDataAfterCreate(Product $product, ProductData $productData)
    {
        $productCategories = $this->productCategoryFactory->createMultiple(
            $product,
            $productData->categories
        );
        $product->setProductCategories($productCategories);
        $this->em->flush($product);

        $this->saveParameters($product, $productData->parameters);
        $this->refreshProductManualInputPrices($product, $productData->manualInputPricesByPricingGroupId);
        $this->refreshProductAccessories($product, $productData->accessories);

        $this->imageFacade->manageImages($product, $productData->images);
        $this->friendlyUrlFacade->createFriendlyUrls('front_product_detail', $product->getId(), $product->getName());
    }

    /**
     * @param int $productId
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public function edit($productId, ProductData $productData)
    {
        $product = $this->productRepository->getById($productId);
        $originalName = $product->getName();

        $productCategories = $this->productCategoryFactory->createMultiple(
            $product,
            $productData->categories
        );
        $product->edit($productCategories, $productData);

        $this->saveParameters($product, $productData->parameters);
        if (!$product->isMainVariant()) {
            $this->refreshProductManualInputPrices($product, $productData->manualInputPricesByPricingGroupId);
        } else {
            $product->refreshVariants($productData->variants);
        }
        $this->refreshProductAccessories($product, $productData->accessories);
        $this->em->flush();
        $this->imageFacade->manageImages($product, $productData->images);
        $this->friendlyUrlFacade->saveUrlListFormData('front_product_detail', $product->getId(), $productData->urls);
        $this->createFriendlyUrlWhenRenamed($product, $originalName);

        $productToExport = $product->isVariant() ? $product->getMainVariant() : $product;
        $this->productExportScheduler->scheduleRowIdForImmediateExport($productToExport->getId());

        return $product;
    }

    /**
     * @param int $productId
     */
    public function delete($productId)
    {
        $product = $this->productRepository->getById($productId);
        $product->getProductDeleteResult();

        $this->productExportScheduler->scheduleRowIdForImmediateExport($product->getId());

        $this->em->remove($product);
        $this->em->flush();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Parameter\ProductParameterValueData[] $productParameterValuesData
     */
    protected function saveParameters(Product $product, array $productParameterValuesData)
    {
        // Doctrine runs INSERTs before DELETEs in UnitOfWork. In case of UNIQUE constraint
        // in database, this leads in trying to insert duplicate entry.
        // That's why it's necessary to do remove and flush first.

        $oldProductParameterValues = $this->parameterRepository->getProductParameterValuesByProduct($product);
        foreach ($oldProductParameterValues as $oldProductParameterValue) {
            $this->em->remove($oldProductParameterValue);
        }
        $this->em->flush($oldProductParameterValues);

        $toFlush = [];
        foreach ($productParameterValuesData as $productParameterValueData) {
            $productParameterValue = $this->productParameterValueFactory->create(
                $product,
                $productParameterValueData->parameter,
                $this->parameterRepository->findOrCreateParameterValueByValueTextAndLocale(
                    $productParameterValueData->parameterValueData->text,
                    $productParameterValueData->parameterValueData->locale
                )
            );
            $this->em->persist($productParameterValue);
            $toFlush[] = $productParameterValue;
        }

        if (count($toFlush) > 0) {
            $this->em->flush($toFlush);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Pricing\ProductSellingPrice[]
     */
    public function getAllProductSellingPrices(Product $product): array
    {
        $productSellingPrices = [];

        foreach ($this->pricingGroupRepository->getPricingGroups() as $pricingGroup) {
            try {
                $sellingPrice = $this->productPriceCalculation->calculatePrice($product, $pricingGroup);
            } catch (MainVariantPriceCalculationException $e) {
                $sellingPrice = new ProductPrice(Price::zero(), false);
            }
            $productSellingPrices[$pricingGroup->getId()] = new ProductSellingPrice($pricingGroup, $sellingPrice);
        }

        return $productSellingPrices;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Component\Money\Money[]|null[] $manualInputPrices
     */
    protected function refreshProductManualInputPrices(Product $product, array $manualInputPrices)
    {
        foreach ($this->pricingGroupRepository->getAll() as $pricingGroup) {
            $this->productManualInputPriceFacade->refresh(
                $product,
                $pricingGroup,
                $manualInputPrices[$pricingGroup->getId()]
            );
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Product[] $accessories
     */
    protected function refreshProductAccessories(Product $product, array $accessories)
    {
        $oldProductAccessories = $this->productAccessoryRepository->getAllByProduct($product);
        foreach ($oldProductAccessories as $oldProductAccessory) {
            $this->em->remove($oldProductAccessory);
        }
        $this->em->flush($oldProductAccessories);

        $toFlush = [];
        foreach ($accessories as $position => $accessory) {
            $newProductAccessory = $this->productAccessoryFactory->create($product, $accessory, $position);
            $this->em->persist($newProductAccessory);
            $toFlush[] = $newProductAccessory;
        }

        if (count($toFlush) > 0) {
            $this->em->flush($toFlush);
        }
    }

    /**
     * @param string $productCatnum
     * @return \App\Model\Product\Product
     */
    public function getOneByCatnumExcludeMainVariants($productCatnum)
    {
        return $this->productRepository->getOneByCatnumExcludeMainVariants($productCatnum);
    }

    /**
     * @param string $uuid
     * @return \App\Model\Product\Product
     */
    public function getByUuid(string $uuid): Product
    {
        return $this->productRepository->getOneByUuid($uuid);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     */
    public function markProductsForExport(array $products): void
    {
        $this->productRepository->markProductsForExport($products);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $originalName
     */
    protected function createFriendlyUrlWhenRenamed(Product $product, string $originalName): void
    {
        $changedName = $this->getChangedNameByLocale($product, $originalName);
        if (!$changedName) {
            return;
        }

        $this->friendlyUrlFacade->createFriendlyUrl(
            'front_product_detail',
            $product->getId(),
            $changedName
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $originalName
     * @return bool
     */
    protected function getChangedNameByLocale(Product $product, string $originalName): bool
    {
        return $product->getName() !== $originalName;
    }

    /**
     * @param int $productId
     * @return \App\Model\Product\Product
     */
    public function getVisibleProductById(int $productId): Product
    {
        return $this->productRepository->getVisible(
            $productId,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getAccessoriesForProduct(Product $product): array
    {
        return $this->productAccessoryRepository->getAllOfferedAccessoriesByProduct(
            $product,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForProduct(Product $product): array
    {
        return $this->productRepository->getAllSellableVariantsByMainVariant(
            $product,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

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
    ): PaginationResult {
        $category = $this->categoryRepository->getById($categoryId);

        return $this->productRepository->getPaginationResultForListableInCategory(
            $category,
            $productFilterData,
            $orderingModeId,
            $this->currentCustomerUser->getPricingGroup(),
            $page,
            $limit
        );
    }

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
    ): PaginationResult {
        $brand = $this->brandRepository->getById($brandId);

        return $this->productRepository->getPaginationResultForListableForBrand(
            $brand,
            $orderingModeId,
            $this->currentCustomerUser->getPricingGroup(),
            $page,
            $limit
        );
    }

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
    ): PaginationResult {
        return $this->productRepository->getPaginationResultForSearchListable(
            $searchText,
            $productFilterData,
            $orderingModeId,
            $this->currentCustomerUser->getPricingGroup(),
            $page,
            $limit
        );
    }

    /**
     * @param string|null $searchText
     * @param int $limit
     * @return \App\Component\Paginator\PaginationResult
     */
    public function getSearchAutocompleteProducts(?string $searchText, int $limit): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();

        $page = 1;

        return $this->productRepository->getPaginationResultForSearchListable(
            $searchText,
            $emptyProductFilterData,
            ProductListOrderingConfig::ORDER_BY_RELEVANCE,
            $this->currentCustomerUser->getPricingGroup(),
            $page,
            $limit
        );
    }

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
    ): ProductFilterCountData {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $this->currentCustomerUser->getPricingGroup(),
            $this->categoryRepository->getById($categoryId)
        );

        return $this->productFilterCountRepository->getProductFilterCountData(
            $productsQueryBuilder,
            $productFilterConfig,
            $productFilterData,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

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
    ): ProductFilterCountData {
        $productsQueryBuilder = $this->productRepository->getListableBySearchTextQueryBuilder(
            $this->currentCustomerUser->getPricingGroup(),
            $searchText
        );

        return $this->productFilterCountRepository->getProductFilterCountData(
            $productsQueryBuilder,
            $productFilterConfig,
            $productFilterData,
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @return array
     */
    public function getAllOfferedProducts(): array
    {
        return $this->productRepository->getAllOfferedProducts(
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @return int
     */
    public function getProductsCount(): int
    {
        $queryBuilder = $this->productRepository->getAllListableQueryBuilder(
            $this->currentCustomerUser->getPricingGroup()
        );

        return $queryBuilder
            ->select('count(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProducts(int $limit, int $offset, string $orderingModeId): array
    {
        $queryBuilder = $this->productRepository->getAllListableTranslatedAndOrderedQueryBuilder(
            $orderingModeId,
            $this->currentCustomerUser->getPricingGroup()
        );

        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($limit);
        $query = $queryBuilder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);

        return $query->execute();
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param int $limit
     * @param int $offset
     * @param string $orderingModeId
     * @return array
     */
    public function getProductsByCategory(Category $category, int $limit, int $offset, string $orderingModeId): array
    {
        $queryBuilder = $this->productRepository->getAllListableTranslatedAndOrderedQueryBuilderByCategory(
            $orderingModeId,
            $this->currentCustomerUser->getPricingGroup(),
            $category
        );

        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($limit);
        $query = $queryBuilder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);

        return $query->execute();
    }
}
