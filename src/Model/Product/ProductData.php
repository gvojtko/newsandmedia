<?php

namespace App\Model\Product;

use App\Component\FileUpload\ImageUploadData;
use App\Component\Router\FriendlyUrl\UrlListData;

class ProductData
{
    /**
     * @var string[]|null[]
     */
    public $name;

    /**
     * @var string|null
     */
    public $catnum;

    /**
     * @var string|null
     */
    public $partno;

    /**
     * @var string|null
     */
    public $ean;

    /**
     * @var \DateTime|null
     */
    public $sellingFrom;

    /**
     * @var \DateTime|null
     */
    public $sellingTo;

    /**
     * @var bool
     */
    public $sellingDenied;

    /**
     * @var bool
     */
    public $hidden;

    /**
     * @var bool
     */
    public $usingStock;

    /**
     * @var int|null
     */
    public $stockQuantity;

    /**
     * @var string Product::OUT_OF_STOCK_ACTION_*
     */
    public $outOfStockAction;

    /**
     * @var \App\Model\Category\Category[]
     */
    public $categories;

    /**
     * @var string[]|null[]
     */
    public $variantAlias;

    /**
     * @var int
     */
    public $orderingPriority;

    /**
     * @var \App\Model\Product\Parameter\ProductParameterValueData[]
     */
    public $parameters;

    /**
     * @var \App\Component\FileUpload\ImageUploadData
     */
    public $images;

    /**
     * @var \App\Component\Money\Money[]|null[]
     */
    public $manualInputPricesByPricingGroupId;

    /**
     * @var string
     */
    public $seoTitle;

    /**
     * @var string
     */
    public $seoMetaDescription;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $shortDescription;

    /**
     * @var \App\Component\Router\FriendlyUrl\UrlListData
     */
    public $urls;

    /**
     * @var \App\Model\Product\Product[]
     */
    public $accessories;

    /**
     * @var \App\Model\Product\Product[]
     */
    public $variants;

    /**
     * @var string
     */
    public $seoH1;

    /**
     * @var array
     */
    public $pluginData;

    /**
     * @var string|null
     */
    public $uuid;

    public function __construct()
    {
        $this->name = [];
        $this->sellingDenied = false;
        $this->hidden = false;
        $this->flags = [];
        $this->usingStock = false;
        $this->categories = [];
        $this->variantAlias = [];
        $this->orderingPriority = 0;
        $this->parameters = [];
        $this->images = new ImageUploadData();
        $this->manualInputPricesByPricingGroupId = [];
        $this->seoTitles = [];
        $this->seoMetaDescriptions = [];
        $this->descriptions = [];
        $this->shortDescriptions = [];
        $this->urls = new UrlListData();
        $this->accessories = [];
        $this->variants = [];
        $this->seoH1s = [];
        $this->pluginData = [];
    }
}
