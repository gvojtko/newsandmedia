<?php

namespace App\Model\Product\Filter;

class ProductFilterConfig
{
    /**
     * @var \App\Model\Product\Filter\ParameterFilterChoice[]
     */
    protected $parameterChoices;

    /**
     * @var \App\Model\Product\Flag\Flag[]
     */
    protected $flagChoices;

    /**
     * @var \App\Model\Product\Brand\Brand[]
     */
    protected $brandChoices;

    /**
     * @var \App\Model\Product\Filter\PriceRange
     */
    protected $priceRange;

    /**
     * @param \App\Model\Product\Filter\ParameterFilterChoice[] $parameterChoices
     * @param \App\Model\Product\Flag\Flag[] $flagChoices
     * @param \App\Model\Product\Brand\Brand[] $brandChoices
     * @param \App\Model\Product\Filter\PriceRange $priceRange
     */
    public function __construct(
        array $parameterChoices,
        array $flagChoices,
        array $brandChoices,
        PriceRange $priceRange
    ) {
        $this->parameterChoices = $parameterChoices;
        $this->flagChoices = $flagChoices;
        $this->brandChoices = $brandChoices;
        $this->priceRange = $priceRange;
    }

    /**
     * @return \App\Model\Product\Filter\ParameterFilterChoice[]
     */
    public function getParameterChoices()
    {
        return $this->parameterChoices;
    }

    /**
     * @return \App\Model\Product\Flag\Flag[]
     */
    public function getFlagChoices()
    {
        return $this->flagChoices;
    }

    /**
     * @return \App\Model\Product\Brand\Brand[]
     */
    public function getBrandChoices()
    {
        return $this->brandChoices;
    }

    /**
     * @return \App\Model\Product\Filter\PriceRange
     */
    public function getPriceRange()
    {
        return $this->priceRange;
    }
}
