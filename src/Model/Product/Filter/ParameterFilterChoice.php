<?php

namespace App\Model\Product\Filter;

use App\Model\Product\Parameter\Parameter;

class ParameterFilterChoice
{
    /**
     * @var \App\Model\Product\Parameter\Parameter
     */
    protected $parameter;

    /**
     * @var \App\Model\Product\Parameter\ParameterValue[]
     */
    protected $values;

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @param \App\Model\Product\Parameter\ParameterValue[] $values
     */
    public function __construct(
        ?Parameter $parameter = null,
        array $values = []
    ) {
        $this->parameter = $parameter;
        $this->values = $values;
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    public function getValues()
    {
        return $this->values;
    }
}
