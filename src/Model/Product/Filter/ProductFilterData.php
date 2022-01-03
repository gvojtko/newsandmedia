<?php

namespace App\Model\Product\Filter;

class ProductFilterData
{
    /**
     * @var \App\Component\Money\Money|null
     */
    public $minimalPrice;

    /**
     * @var \App\Component\Money\Money|null
     */
    public $maximalPrice;

    /**
     * @var \App\Model\Product\Filter\ParameterFilterData[]
     */
    public $parameters = [];

    /**
     * @var bool
     */
    public $inStock;

    /**
     * @var \App\Model\Product\Flag\Flag[]
     */
    public $flags = [];

    /**
     * @var \App\Model\Product\Brand\Brand[]
     */
    public $brands = [];

    public function __construct()
    {
        $this->inStock = false;
    }
}
