<?php

namespace App\Form\Front\Product;

use App\Model\Product\Filter\ParameterFilterData;
use App\Model\Product\Filter\ProductFilterConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParameterFilterFormType extends AbstractType implements DataTransformerInterface
{
    /**
     * @var \App\Model\Product\Filter\ParameterFilterChoice[]
     */
    private $parameterChoicesIndexedByParameterId;

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \App\Model\Product\Filter\ProductFilterConfig $config */
        $config = $options['product_filter_config'];

        $this->parameterChoicesIndexedByParameterId = [];
        foreach ($config->getParameterChoices() as $parameterChoice) {
            $parameter = $parameterChoice->getParameter();
            $parameterValues = $parameterChoice->getValues();

            $this->parameterChoicesIndexedByParameterId[$parameter->getId()] = $parameterChoice;

            $builder->add($parameter->getId(), ChoiceType::class, [
                'label' => $parameter->getName(),
                'choices' => $parameterValues,
                'choice_label' => 'text',
                'choice_value' => 'id',
                'choice_name' => 'id',
                'multiple' => true,
                'expanded' => true,
            ]);
        }

        $builder->addViewTransformer($this);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('product_filter_config')
            ->setAllowedTypes('product_filter_config', ProductFilterConfig::class)
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue[][]|null $value
     * @return \App\Model\Product\Filter\ParameterFilterData[]|null
     */
    public function reverseTransform($value)
    {
        if ($value === null) {
            return null;
        }

        $parametersFilterData = [];
        foreach ($value as $parameterId => $parameterValues) {
            if (!array_key_exists($parameterId, $this->parameterChoicesIndexedByParameterId)) {
                continue; // invalid parameter IDs are ignored
            }

            $parameterFilterData = new ParameterFilterData();
            $parameterFilterData->parameter = $this->parameterChoicesIndexedByParameterId[$parameterId]->getParameter();
            $parameterFilterData->values = $parameterValues;
            $parametersFilterData[] = $parameterFilterData;
        }

        return $parametersFilterData;
    }

    /**
     * @param \App\Model\Product\Filter\ParameterFilterData[]|null $value
     * @return \App\Model\Product\Parameter\ParameterValue[][]|null
     */
    public function transform($value)
    {
        if ($value === null) {
            return null;
        }

        $parameterValuesIndexedByParameterId = [];
        foreach ($value as $parameterFilterData) {
            $parameterId = $parameterFilterData->parameter->getId();
            $parameterValuesIndexedByParameterId[$parameterId] = $parameterFilterData->values;
        }

        return $parameterValuesIndexedByParameterId;
    }
}
