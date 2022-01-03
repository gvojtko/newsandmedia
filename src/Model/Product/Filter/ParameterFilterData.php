<?php

namespace App\Model\Product\Filter;

class ParameterFilterData
{
    /**
     * @var \App\Model\Product\Parameter\Parameter|null
     */
    public $parameter;

    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    public $values = [];
}
