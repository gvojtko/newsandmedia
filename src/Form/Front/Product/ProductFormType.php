<?php

namespace App\Form\Fron\Product;

use App\Component\Plugin\PluginCrudExtensionFacade;
use App\Form\Admin\Product\Parameter\ProductParameterValueFormType;
use App\Form\Constraints\NotNegativeMoneyAmount;
use App\Form\Constraints\UniqueProductParameters;
use App\Form\DisplayOnlyType;
use App\Form\DisplayOnlyUrlType;
use App\Form\GroupType;
use App\Form\ImageUploadType;
use App\Form\Locale\LocalizedType;
use App\Form\ProductCalculatedPricesType;
use App\Form\ProductParameterValueType;
use App\Form\ProductsType;
use App\Form\Transformers\ProductParameterValueToProductParameterValuesLocalizedTransformer;
use App\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use App\Form\UrlListType;
use App\Form\ValidationGroup;
use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Seo\SeoSettingFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ProductFormType extends AbstractType
{
    public const VALIDATION_GROUP_USING_STOCK = 'usingStock';
    public const VALIDATION_GROUP_USING_STOCK_AND_ALTERNATE_AVAILABILITY = 'usingStockAndAlternateAvailability';
    public const VALIDATION_GROUP_NOT_USING_STOCK = 'notUsingStock';
    public const CSRF_TOKEN_ID = 'product_edit_type';

    /**
     * @var \App\Model\Seo\SeoSettingFacade
     */
    private $seoSettingFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Form\Transformers\RemoveDuplicatesFromArrayTransformer
     */
    private $removeDuplicatesTransformer;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Component\Plugin\PluginCrudExtensionFacade
     */
    private $pluginDataFormExtensionFacade;

    /**
     * @var \App\Form\Transformers\ProductParameterValueToProductParameterValuesLocalizedTransformer
     */
    private $productParameterValueToProductParameterValuesLocalizedTransformer;

    /**
     * @param \App\Model\Seo\SeoSettingFacade $seoSettingFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Form\Transformers\RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Component\Plugin\PluginCrudExtensionFacade $pluginDataFormExtensionFacade
     * @param \App\Form\Transformers\ProductParameterValueToProductParameterValuesLocalizedTransformer $productParameterValueToProductParameterValuesLocalizedTransformer
     */
    public function __construct(
        SeoSettingFacade $seoSettingFacade,
        CategoryFacade $categoryFacade,
        RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer,
        PricingGroupFacade $pricingGroupFacade,
        PluginCrudExtensionFacade $pluginDataFormExtensionFacade,
        ProductParameterValueToProductParameterValuesLocalizedTransformer $productParameterValueToProductParameterValuesLocalizedTransformer
    ) {
        $this->seoSettingFacade = $seoSettingFacade;
        $this->categoryFacade = $categoryFacade;
        $this->removeDuplicatesTransformer = $removeDuplicatesTransformer;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->pluginDataFormExtensionFacade = $pluginDataFormExtensionFacade;
        $this->productParameterValueToProductParameterValuesLocalizedTransformer = $productParameterValueToProductParameterValuesLocalizedTransformer;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \App\Model\Product\Product|null $product */
        $product = $options['product'];
        $disabledItemInMainVariantAttr = [];
        if ($this->isProductMainVariant($product)) {
            $disabledItemInMainVariantAttr = [
                'disabledField' => true,
                'disabledFieldTitle' => t('This item can be set in product detail of a specific variant'),
                'disabledFieldClass' => 'form-line__disabled',
            ];
        }

        $builder->add('name', TextType::class, [
            'required' => false,
            'entry_options' => [
                'constraints' => [
                    new Constraints\Length(
                        ['max' => 255, 'maxMessage' => 'Product name cannot be longer than {{ limit }} characters']
                    ),
                ],
            ],
            'label' => t('Name'),
            'render_form_row' => false,
        ]);

        if ($this->isProductVariant($product) || $this->isProductMainVariant($product)) {
            $builder->add($this->createVariantGroup($builder, $product));
        }

        $builder->add($this->createBasicInformationGroup($builder, $product, $disabledItemInMainVariantAttr));
        $builder->add($this->createCategoriesGroup($builder, $product, $disabledItemInMainVariantAttr));
        $builder->add($this->createPricesGroup($builder, $product));
        $builder->add($this->createDescriptionsGroup($builder, $product));
        $builder->add($this->createShortDescriptionsGroup($builder, $product));
        $builder->add($this->createParametersGroup($builder));
        $builder->add($this->createSeoGroup($builder, $product));
        $builder->add($this->createImagesGroup($builder, $options));
        $builder->add('save', SubmitType::class);
        $this->pluginDataFormExtensionFacade->extendForm($builder, 'product', 'pluginData');
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('product')
            ->setAllowedTypes('product', [Product::class, 'null'])
            ->setDefaults([
                'data_class' => ProductData::class,
                'attr' => ['novalidate' => 'novalidate'],
                'csrf_token_id' => self::CSRF_TOKEN_ID,
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];
                    /** @var \App\Model\Product\ProductData $productData */
                    $productData = $form->getData();
                    if ($productData->usingStock) {
                        $validationGroups[] = static::VALIDATION_GROUP_USING_STOCK;
                        if ($productData->outOfStockAction === Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY) {
                            $validationGroups[] = static::VALIDATION_GROUP_USING_STOCK_AND_ALTERNATE_AVAILABILITY;
                        }
                    } else {
                        $validationGroups[] = static::VALIDATION_GROUP_NOT_USING_STOCK;
                    }

                    return $validationGroups;
                },
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @param array $disabledItemInMainVariantAttr
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createBasicInformationGroup(FormBuilderInterface $builder, ?Product $product, $disabledItemInMainVariantAttr = [])
    {
        $builderBasicInformationGroup = $builder->create('basicInformationGroup', GroupType::class, [
            'label' => t('Basic information'),
        ]);

        $builderBasicInformationGroup->add('catnum', TextType::class, [
            'required' => false,
            'constraints' => [
                new Constraints\Length(
                    ['max' => 100, 'maxMessage' => 'Catalog number cannot be longer than {{ limit }} characters']
                ),
            ],
            'disabled' => $this->isProductMainVariant($product),
            'attr' => $disabledItemInMainVariantAttr,
            'label' => t('Catalog number'),
        ])
            ->add('partno', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(
                        ['max' => 100, 'maxMessage' => 'Part number cannot be longer than {{ limit }} characters']
                    ),
                ],
                'disabled' => $this->isProductMainVariant($product),
                'attr' => $disabledItemInMainVariantAttr,
                'label' => t('PartNo (serial number)'),
            ])
            ->add('ean', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(
                        ['max' => 100, 'maxMessage' => 'EAN cannot be longer than {{ limit }} characters']
                    ),
                ],
                'disabled' => $this->isProductMainVariant($product),
                'attr' => $disabledItemInMainVariantAttr,
                'label' => t('EAN'),
            ]);

        if ($product !== null) {
            $builderBasicInformationGroup->add('id', DisplayOnlyType::class, [
                'label' => t('ID'),
                'data' => $product->getId(),
            ]);
        }

        return $builderBasicInformationGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createCategoriesGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $categoriesGroupGroup = $builder->create('categoriesGroup', GroupType::class, [
            'label' => t('Categories'),
        ]);

        $categoriesGroupGroup->add('categories', ChoiceType::class, [
            'choices' => $this->categoryFacade->getAll(),
            'placeholder' => t('-- Choose categories --'),
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please choose at least one category']),
            ],
            'choice_label' => 'name',
            'choice_value' => 'id',
            'multiple' => false,
            'expanded' => false,
        ]);

        return $categoriesGroupGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createShortDescriptionsGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $builderShortDescriptionGroup = $builder->create('shortDescriptionsGroup', GroupType::class, [
            'label' => t('Short description'),
        ]);

        if ($this->isProductVariant($product)) {
            $builderShortDescriptionGroup->add('shortDescriptions', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => false,
                'data' => t('Short description can be set in the main variant.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual font-size-13',
                ],
            ]);
        } else {
            $builderShortDescriptionGroup
                ->add('shortDescriptions', TextType::class, [
                    'entry_type' => TextareaType::class,
                    'required' => false,
                    'disabled' => $this->isProductVariant($product),
                ]);
        }

        return $builderShortDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createDescriptionsGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $builderDescriptionGroup = $builder->create('descriptionsGroup', GroupType::class, [
            'label' => t('Description'),
        ]);

        if ($this->isProductVariant($product)) {
            $builderDescriptionGroup->add('descriptions', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => false,
                'data' => t('Description can be set on product detail of the main product.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual font-size-13',
                ],
            ]);
        } else {
            $builderDescriptionGroup
                ->add('descriptions', TextareaType::class, [
                    'required' => false,
                    'disabled' => $this->isProductVariant($product),
                ]);
        }

        return $builderDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createPricesGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $builderPricesGroup = $builder->create('pricesGroup', GroupType::class, [
            'label' => t('Prices'),
        ]);

        $productCalculatedPricesGroup = $builder->create(
            'productCalculatedPricesGroup',
            ProductCalculatedPricesType::class,
            [
                'product' => $product,
                'inherit_data' => true,
                'render_form_row' => false,
            ]
        );

        $builderPricesGroup->add($productCalculatedPricesGroup);
        $manualInputPricesByPricingGroup = $builder->create('manualInputPricesByPricingGroupId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
            'disabled' => $this->isProductMainVariant($product),
        ]);

        foreach ($this->pricingGroupFacade->getAll() as $pricingGroup) {
            $manualInputPricesByPricingGroup->add((string)$pricingGroup->getId(), MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => $pricingGroup->getName(),
            ]);
        }
        $productCalculatedPricesGroup->add($manualInputPricesByPricingGroup);

        $builderPricesGroup->add($productCalculatedPricesGroup);

        if ($this->isProductMainVariant($product)) {
            $builderPricesGroup->remove('productCalculatedPricesGroup');
            $builderPricesGroup->add('disabledPricesOnMainVariant', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => true,
                'data' => t('You can set the prices on product detail of specific variant.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual font-size-13',
                ],
            ]);
        }

        return $builderPricesGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createSeoGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $seoTitlesOption = [
            'attr' => [
                'placeholder' => $this->getTitlePlaceholder($product),
                'class' => 'js-dynamic-placeholder',
                'data-placeholder-source-input-id' => 'product_form_name',
            ],
        ];
        $seoMetaDescriptionsOption = [
            'attr' => [
                'placeholder' => $this->seoSettingFacade->getDescriptionMainPage(),
            ],
        ];
        $seoH1Option = $seoTitlesOption;

        $builderSeoGroup = $builder->create('seoGroup', GroupType::class, [
            'label' => t('Seo'),
        ]);

        $builderSeoGroup
            ->add('seoTitles', TextType::class, [
                'required' => false,
                'options_by_domain_id' => $seoTitlesOption,
                'macro' => [
                    'name' => 'seoFormRowMacros.row',
                    'recommended_length' => 60,
                ],
                'label' => t('Page title'),
            ])
            ->add('seoMetaDescriptions', TextareaType::class, [
                'required' => false,
                'options_by_domain_id' => $seoMetaDescriptionsOption,
                'macro' => [
                    'name' => 'seoFormRowMacros.row',
                    'recommended_length' => 155,
                ],
                'label' => t('Meta description'),
            ])
            ->add('seoH1s', TextType::class, [
                'entry_type' => TextType::class,
                'required' => false,
                'options_by_domain_id' => $seoH1Option,
                'label' => t('Heading (H1)'),
            ]);

        if ($product) {
            $builderSeoGroup->add('urls', UrlListType::class, [
                'route_name' => 'front_product_detail',
                'entity_id' => $product->getId(),
                'label' => t('URL settings'),
            ]);
        }

        return $builderSeoGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createVariantGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $variantGroup = $builder->create('variantGroup', FormType::class, [
            'inherit_data' => true,
            'attr' => [
                'class' => 'wrap-border',
            ],
            'render_form_row' => false,
        ]);

        if ($this->isProductVariant($product)) {
            $variantGroup->add('mainVariantUrl', DisplayOnlyUrlType::class, [
                'label' => t('Product is variant'),
                'route' => 'admin_product_edit',
                'route_params' => [
                    'id' => $product->getMainVariant()->getId(),
                ],
                'route_label' => $product->getMainVariant()->getName(),
            ]);

            $variantGroup->add('variantAlias', LocalizedType::class, [
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Constraints\Length(
                            ['max' => 255, 'maxMessage' => 'Variant alias cannot be longer than {{ limit }} characters']
                        ),
                    ],
                ],
                'label' => t('Variant alias'),
                'render_form_row' => true,
            ]);
        }

        if ($this->isProductMainVariant($product)) {
            $variantGroup->add('variants', ProductsType::class, [
                'required' => false,
                'main_product' => $product,
                'allow_main_variants' => false,
                'allow_variants' => false,
                'label_button_add' => t('Add variant'),
                'label' => t('Variants'),
                'top_info_title' => t('Product is main variant.'),
            ]);
        }

        return $variantGroup;
    }

    /**
     * @param \App\Model\Product\Product|null $product
     * @return string
     */
    private function getTitlePlaceholder(?Product $product = null)
    {
        return $product !== null ? $product->getName() : '';
    }

    /**
     * @param \App\Model\Product\Product|null $product
     * @return bool
     */
    private function isProductMainVariant(?Product $product)
    {
        return $product !== null && $product->isMainVariant();
    }

    /**
     * @param \App\Model\Product\Product|null $product
     * @return bool
     */
    private function isProductVariant(?Product $product)
    {
        return $product !== null && $product->isVariant();
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createParametersGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderParametersGroup = $builder->create('parametersGroup', GroupType::class, [
            'label' => t('Parameters'),
        ]);

        $builderParametersGroup
            ->add($builder->create('parameters', ProductParameterValueType::class, [
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => ProductParameterValueFormType::class,
                'constraints' => [
                    new UniqueProductParameters([
                        'message' => 'Each parameter can be used only once',
                    ]),
                ],
                'invalid_message' => 'Each parameter can be used only once',
                'error_bubbling' => false,
                'render_form_row' => false,
            ])
                ->addViewTransformer($this->productParameterValueToProductParameterValuesLocalizedTransformer));

        return $builderParametersGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createImagesGroup(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        $builderImageGroup = $builder->create('imageGroup', GroupType::class, [
            'label' => t('Images'),
        ]);
        $builderImageGroup
            ->add('images', ImageUploadType::class, [
                'required' => false,
                'image_entity_class' => Product::class,
                'multiple' => true,
                'limit_files' => 3,
                'file_constraints' => [
                    new Constraints\Image([
                        'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                        'mimeTypesMessage' => 'Image can be only in JPG, GIF or PNG format',
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Uploaded image is to large ({{ size }} {{ suffix }}). '
                            . 'Maximum size of an image is {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'entity' => $options['product'],
                'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
                'label' => t('Images'),
            ]);

        return $builderImageGroup;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }
}
