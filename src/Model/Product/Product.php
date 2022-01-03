<?php

namespace App\Model\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Product
 *
 * @ORM\Table(
 *     name="products",
 * )
 * @ORM\Entity
 */
class Product
{
    public const OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY = 'setAlternateAvailability';
    public const OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE = 'excludeFromSale';
    public const OUT_OF_STOCK_ACTION_HIDE = 'hide';
    public const VARIANT_TYPE_NONE = 'none';
    public const VARIANT_TYPE_MAIN = 'main';
    public const VARIANT_TYPE_VARIANT = 'variant';

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoTitle;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoMetaDescription;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $shortDescription;

    /**
     * @var string
     * @ORM\Column(type="tsvector", nullable=false)
     */
    protected $descriptionTsvector;

    /**
     * @var string
     * @ORM\Column(type="tsvector", nullable=false)
     */
    protected $fulltextTsvector;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoH1;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $catnum;

    /**
     * @var string
     * @ORM\Column(type="tsvector", nullable=false)
     */
    protected $catnumTsvector;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $partno;

    /**
     * @var string
     * @ORM\Column(type="tsvector", nullable=false)
     */
    protected $partnoTsvector;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $ean;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sellingFrom;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sellingTo;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $sellingDenied;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $calculatedSellingDenied;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $hidden;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $calculatedHidden;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $usingStock;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $stockQuantity;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $outOfStockAction;

    /**
     * @var \App\Model\Product\ProductCategory[]|\Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(
     *   targetEntity="App\Model\Product\ProductCategory",
     *   mappedBy="product",
     *   orphanRemoval=true,
     *   cascade={"persist"}
     * )
     */
    protected $productCategories;

    /**
     * @var \App\Model\Product\Product[]|\Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="App\Model\Product\Product", mappedBy="mainVariant", cascade={"persist"})
     */
    protected $variants;

    /**
     * @var \App\Model\Product\Product|null
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product", inversedBy="variants", cascade={"persist"})
     * @ORM\JoinColumn(name="main_variant_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $mainVariant;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    protected $variantType;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $orderingPriority;

    /**
     * @var string
     * @ORM\Column(type="guid", unique=true)
     */
    protected $uuid;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $exportProduct;

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product[]|null $variants
     */
    protected function __construct(ProductData $productData, ?array $variants = null)
    {
        $this->translations = new ArrayCollection();
        $this->domains = new ArrayCollection();
        $this->catnum = $productData->catnum;
        $this->partno = $productData->partno;
        $this->ean = $productData->ean;
        $this->setAvailabilityAndStock($productData);
        $this->productCategories = new ArrayCollection();
        $this->flags = new ArrayCollection($productData->flags);
        $this->calculatedHidden = true;
        $this->calculatedSellingDenied = true;
        $this->exportProduct = true;

        $this->variants = new ArrayCollection();
        if ($variants === null) {
            $this->variantType = self::VARIANT_TYPE_NONE;
        } else {
            $this->variantType = self::VARIANT_TYPE_MAIN;
            $this->addVariants($variants);
        }

        $this->uuid = $productData->uuid ?: Uuid::uuid4()->toString();
        $this->setData($productData);
    }

    /**
     * @param \App\Model\Product\ProductCategory[] $productCategories
     * @param \App\Model\Product\ProductData $productData
     */
    public function edit(
        array $productCategories,
        ProductData $productData
    ) {
        $this->editFlags($productData->flags);

        if (!$this->isVariant()) {
            $this->setProductCategories($productCategories);
        }
        if (!$this->isMainVariant()) {
            $this->setAvailabilityAndStock($productData);
            $this->catnum = $productData->catnum;
            $this->partno = $productData->partno;
            $this->ean = $productData->ean;
        }
        $this->setData($productData);

        $this->markForVisibilityRecalculation();
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    protected function setData(ProductData $productData): void
    {
        $this->sellingFrom = $productData->sellingFrom;
        $this->sellingTo = $productData->sellingTo;
        $this->sellingDenied = $productData->sellingDenied;
        $this->hidden = $productData->hidden;
        $this->brand = $productData->brand;
        $this->unit = $productData->unit;
        $this->name = $productData->name;
        $this->orderingPriority = $productData->orderingPriority;
        $this->seoTitle = $productData->seoTitle;
        $this->seoH1 = $productData->seoH1;
        $this->seoMetaDescription = $productData->seoMetaDescription;
        $this->description = $productData->description;
        $this->shortDescription = $productData->shortDescription;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public static function create(ProductData $productData)
    {
        return new static($productData, null);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Product\Product
     */
    public static function createMainVariant(ProductData $productData, array $variants)
    {
        return new static($productData, $variants);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    protected function setAvailabilityAndStock(ProductData $productData): void
    {
        $this->usingStock = $productData->usingStock;
        if ($this->usingStock) {
            $this->stockQuantity = $productData->stockQuantity;
            $this->outOfStockAction = $productData->outOfStockAction;
        } else {
            $this->stockQuantity = null;
            $this->outOfStockAction = null;
            $this->outOfStockAvailability = null;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCatnum()
    {
        return $this->catnum;
    }

    /**
     * @return string|null
     */
    public function getPartno()
    {
        return $this->partno;
    }

    /**
     * @return string|null
     */
    public function getEan()
    {
        return $this->ean;
    }

    /**
     * @return \DateTime|null
     */
    public function getSellingFrom()
    {
        return $this->sellingFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getSellingTo()
    {
        return $this->sellingTo;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @return bool
     */
    public function getCalculatedHidden()
    {
        return $this->calculatedHidden;
    }

    /**
     * @return bool
     */
    public function isSellingDenied()
    {
        return $this->sellingDenied;
    }

    /**
     * @return bool
     */
    public function getCalculatedSellingDenied()
    {
        return $this->calculatedSellingDenied;
    }

    /**
     * @return bool
     */
    public function isUsingStock()
    {
        return $this->usingStock;
    }

    /**
     * @return int|null
     */
    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    /**
     * @return \App\Model\Product\Unit\Unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return string
     */
    public function getOutOfStockAction()
    {
        return $this->outOfStockAction;
    }

    /**
     * @return int
     */
    public function getOrderingPriority()
    {
        return $this->orderingPriority;
    }

    /**
     * @param \App\Model\Product\ProductCategory[] $productCategories
     */
    public function setProductCategories(array $productCategories)
    {
        foreach ($this->productCategories as $productCategoryDomain) {
            if ($this->isProductCategoryInArray($productCategoryDomain, $productCategories) === false) {
                $this->productCategories->removeElement($productCategoryDomain);
            }
        }
        foreach ($productCategories as $productCategoryDomain) {
            if ($this->isProductCategoryInArray(
                    $productCategoryDomain,
                    $this->productCategories->toArray()
                ) === false) {
                $this->productCategories->add($productCategoryDomain);
            }
        }
        if (!$this->isMainVariant()) {
            return;
        }

        foreach ($this->getVariants() as $variant) {
            $variant->copyProductCategories($productCategories);
        }
    }

    /**
     * @param \App\Model\Product\ProductCategory $searchProductCategory
     * @param \App\Model\Product\ProductCategory[] $productCategories
     * @return bool
     */
    protected function isProductCategoryInArray(ProductCategory $searchProductCategory, array $productCategories): bool
    {
        foreach ($productCategories as $productCategory) {
            if ($productCategory->getCategory() === $searchProductCategory->getCategory()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getCategories()
    {
        $categories = [];

        foreach ($this->productCategories as $productCategory) {
            $categories[] = $productCategory->getCategory();
        }

        return $categories;
    }

    /**
     * @param bool $recalculateVisibility
     */
    protected function setRecalculateVisibility($recalculateVisibility)
    {
        $this->recalculateVisibility = $recalculateVisibility;
    }

    public function markForVisibilityRecalculation()
    {
        $this->setRecalculateVisibility(true);
        if ($this->isMainVariant()) {
            foreach ($this->getVariants() as $variant) {
                $variant->setRecalculateVisibility(true);
            }
        } elseif ($this->isVariant()) {
            $mainVariant = $this->getMainVariant();
            /**
             * When the product is fetched from persistence, the mainVariant is only a proxy object,
             * when we call something on this proxy object, Doctrine fetches it from persistence too.
             *
             * The problem is the Doctrine seems to not fetch the main variant when we only write something into it,
             * but when we read something first, Doctrine fetches the object, and the use-case works correctly.
             *
             * If you think this is strange and it shouldn't work even before the code was moved to Product, you are right, this is strange.
             * When the code is outside of Product, Doctrine does the job correctly, but once the code is inside of Product,
             * Doctrine seems to not fetching the main variant.
             */
            $mainVariant->isMarkedForVisibilityRecalculation();
            $mainVariant->setRecalculateVisibility(true);
        }
    }

    public function markForExport(): void
    {
        $this->exportProduct = true;
    }

    /**
     * @return bool
     */
    public function isMainVariant()
    {
        return $this->variantType === self::VARIANT_TYPE_MAIN;
    }

    /**
     * @return bool
     */
    public function isVariant()
    {
        return $this->variantType === self::VARIANT_TYPE_VARIANT;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getMainVariant()
    {
        if (!$this->isVariant()) {
            throw new ProductIsNotVariantException();
        }

        return $this->mainVariant;
    }

    /**
     * @param \App\Model\Product\Product $variant
     */
    public function addVariant(self $variant)
    {
        if (!$this->isMainVariant()) {
            throw new VariantCanBeAddedOnlyToMainVariantException(
                $this->getId(),
                $variant->getId()
            );
        }
        if ($variant->isMainVariant()) {
            throw new MainVariantCannotBeVariantException($variant->getId());
        }
        if ($variant->isVariant()) {
            throw new ProductIsAlreadyVariantException($variant->getId());
        }

        if ($this->variants->contains($variant)) {
            return;
        }

        $this->variants->add($variant);
        $variant->setMainVariant($this);
        $variant->copyProductCategories($this->productCategories->toArray());
    }

    /**
     * @param \App\Model\Product\ProductCategory[] $productCategories
     */
    protected function copyProductCategories(array $productCategories)
    {
        $newProductCategories = [];

        foreach ($productCategories as $productCategoryDomain) {
            $copiedProductCategory = clone $productCategoryDomain;
            $copiedProductCategory->setProduct($this);
            $newProductCategories[] = $copiedProductCategory;
        }
        $this->setProductCategories($newProductCategories);
    }

    /**
     * @param \App\Model\Product\Product[] $variants
     */
    protected function addVariants(array $variants)
    {
        foreach ($variants as $variant) {
            $this->addVariant($variant);
        }
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getVariants()
    {
        return $this->variants->toArray();
    }

    public function unsetMainVariant()
    {
        if (!$this->isVariant()) {
            throw new ProductIsNotVariantException();
        }
        $this->variantType = self::VARIANT_TYPE_NONE;
        $this->mainVariant->variants->removeElement($this);
        $this->mainVariant = null;
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     */
    protected function setMainVariant(self $mainVariant)
    {
        $this->variantType = self::VARIANT_TYPE_VARIANT;
        $this->mainVariant = $mainVariant;
    }

    /**
     * @param int $quantity
     */
    public function addStockQuantity($quantity)
    {
        $this->stockQuantity += $quantity;
    }

    /**
     * @param int $quantity
     */
    public function subtractStockQuantity($quantity)
    {
        $this->stockQuantity -= $quantity;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getShortDescription(int $domainId)
    {
        return $this->shortDescription;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getDescription(int $domainId)
    {
        return $this->description;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoH1(int $domainId)
    {
        return $this->seoH1;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoTitle(int $domainId)
    {
        return $this->seoTitle;
    }

    /**
     * @param int $domainId
     * @return string|null
     */
    public function getSeoMetaDescription(int $domainId)
    {
        return $this->seoMetaDescription;
    }

    /**
     * @param \App\Model\Product\Product[] $currentVariants
     */
    public function refreshVariants(array $currentVariants): void
    {
        $this->unsetRemovedVariants($currentVariants);
        $this->addNewVariants($currentVariants);
    }

    /**
     * @param \App\Model\Product\Product[] $currentVariants
     */
    protected function addNewVariants(array $currentVariants): void
    {
        foreach ($currentVariants as $currentVariant) {
            if (!in_array($currentVariant, $this->getVariants(), true)) {
                $this->addVariant($currentVariant);
            }
        }
    }

    /**
     * @param \App\Model\Product\Product[] $currentVariants
     */
    protected function unsetRemovedVariants(array $currentVariants)
    {
        foreach ($this->getVariants() as $originalVariant) {
            if (!in_array($originalVariant, $currentVariants, true)) {
                $originalVariant->unsetMainVariant();
            }
        }
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }
}
