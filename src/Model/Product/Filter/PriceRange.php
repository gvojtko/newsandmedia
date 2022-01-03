<?php

namespace App\Model\Product\Filter;

use App\Component\Money\Money;

class PriceRange
{
    /**
     * @var \App\Component\Money\Money
     */
    protected $minimalPrice;

    /**
     * @var \App\Component\Money\Money
     */
    protected $maximalPrice;

    /**
     * @param \App\Component\Money\Money $minimalPrice
     * @param \App\Component\Money\Money $maximalPrice
     */
    public function __construct(Money $minimalPrice, Money $maximalPrice)
    {
        $this->minimalPrice = $minimalPrice;
        $this->maximalPrice = $maximalPrice;
    }

    /**
     * @return \App\Component\Money\Money
     */
    public function getMinimalPrice()
    {
        return $this->minimalPrice;
    }

    /**
     * @return \App\Component\Money\Money
     */
    public function getMaximalPrice()
    {
        return $this->maximalPrice;
    }
}
