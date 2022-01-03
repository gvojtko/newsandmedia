<?php

namespace App\Model\Product\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use App\Component\Doctrine\GroupedScalarHydrator;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ParameterValue;
use App\Model\Product\Parameter\ProductParameterValue;
use App\Model\Product\ProductRepository;

class ParameterFilterChoiceRepository
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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository
    ) {
        $this->em = $em;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Filter\ParameterFilterChoice[]
     */
    public function getParameterFilterChoicesInCategory(
        PricingGroup $pricingGroup,
        Category $category
    ) {
        $productsQueryBuilder = $this->productRepository->getListableInCategoryQueryBuilder(
            $pricingGroup,
            $category
        );

        $productsQueryBuilder
            ->select('MIN(p), pp, pv')
            ->join(ProductParameterValue::class, 'ppv', Join::WITH, 'ppv.product = p')
            ->join(Parameter::class, 'pp', Join::WITH, 'pp = ppv.parameter')
            ->join(ParameterValue::class, 'pv', Join::WITH, 'pv = ppv.value')
            ->groupBy('pp, pv')
            ->resetDQLPart('orderBy');

        $rows = $productsQueryBuilder->getQuery()->execute(null, GroupedScalarHydrator::HYDRATION_MODE);

        $visibleParametersIndexedById = $this->getVisibleParametersIndexedByIdOrderedByName($rows);
        $parameterValuesIndexedByParameterId = $this->getParameterValuesIndexedByParameterIdOrderedByValueText(
            $rows,
        );
        $parameterFilterChoices = [];

        foreach ($visibleParametersIndexedById as $parameterId => $parameter) {
            if (array_key_exists($parameterId, $parameterValuesIndexedByParameterId)) {
                $parameterFilterChoices[] = new ParameterFilterChoice(
                    $parameter,
                    $parameterValuesIndexedByParameterId[$parameterId]
                );
            }
        }

        return $parameterFilterChoices;
    }

    /**
     * @param array $rows
     * @return \App\Model\Product\Parameter\Parameter[]
     */
    protected function getVisibleParametersIndexedByIdOrderedByName(array $rows)
    {
        $parameterIds = [];
        foreach ($rows as $row) {
            $parameterIds[$row['pp']['id']] = $row['pp']['id'];
        }

        $parametersQueryBuilder = $this->em->createQueryBuilder()
            ->select('pp')
            ->from(Parameter::class, 'pp')
            ->where('pp.id IN (:parameterIds)')
            ->andWhere('pp.visible = true')
            ->orderBy('pp.name', 'asc');
        $parametersQueryBuilder->setParameter('parameterIds', $parameterIds);
        $parameters = $parametersQueryBuilder->getQuery()->execute();

        $parametersIndexedById = [];
        /** @var \App\Model\Product\Parameter\Parameter $parameter */
        foreach ($parameters as $parameter) {
            $parametersIndexedById[$parameter->getId()] = $parameter;
        }

        return $parametersIndexedById;
    }

    /**
     * @param array $rows
     * @return \App\Model\Product\Parameter\ParameterValue[][]
     */
    protected function getParameterValuesIndexedByParameterIdOrderedByValueText(array $rows)
    {
        $parameterIdsByValueId = [];
        foreach ($rows as $row) {
            $valueId = $row['pv']['id'];
            $parameterId = $row['pp']['id'];
            $parameterIdsByValueId[$valueId][] = $parameterId;
        }

        $valuesIndexedById = $this->getParameterValuesIndexedByIdOrderedByText($rows);

        $valuesIndexedByParameterId = [];
        foreach ($valuesIndexedById as $valueId => $value) {
            foreach ($parameterIdsByValueId[$valueId] as $parameterId) {
                $valuesIndexedByParameterId[$parameterId][] = $value;
            }
        }

        return $valuesIndexedByParameterId;
    }

    /**
     * @param array $rows
     * @return \App\Model\Product\Parameter\ParameterValue[]
     */
    protected function getParameterValuesIndexedByIdOrderedByText(array $rows)
    {
        $valueIds = [];
        foreach ($rows as $row) {
            $valueId = $row['pv']['id'];
            $valueIds[$valueId] = $valueId;
        }

        $valuesQueryBuilder = $this->em->createQueryBuilder()
            ->select('pv')
            ->from(ParameterValue::class, 'pv')
            ->where('pv.id IN (:valueIds)')
            ->orderBy('pv.text', 'asc');
        $valuesQueryBuilder->setParameter('valueIds', $valueIds);
        $values = $valuesQueryBuilder->getQuery()->execute();

        $valuesIndexedById = [];
        /** @var \App\Model\Product\Parameter\ParameterValue $value */
        foreach ($values as $value) {
            $valuesIndexedById[$value->getId()] = $value;
        }

        return $valuesIndexedById;
    }
}
