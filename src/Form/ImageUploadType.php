<?php

namespace App\Form;

use App\Component\FileUpload\ImageUploadData;
use App\Model\Image\Config\ImageConfig;
use App\Model\Image\ImageFacade;
use App\Model\Image\Processing\ImageProcessor;
use App\Form\Constraints\FileAllowedExtension;
use App\Form\Transformers\ImagesIdsToImagesTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageUploadType extends AbstractType
{
    /**
     * @var \App\Model\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \App\Form\Transformers\ImagesIdsToImagesTransformer
     */
    private $imagesIdsToImagesTransformer;

    /**
     * @var \App\Model\Image\Config\ImageConfig
     */
    private $imageConfig;

    /**
     * @param \App\Model\Image\ImageFacade $imageFacade
     * @param \App\Form\Transformers\ImagesIdsToImagesTransformer $imagesIdsToImagesTransformer
     * @param \App\Model\Image\Config\ImageConfig $imageConfig
     */
    public function __construct(
        ImageFacade $imageFacade,
        ImagesIdsToImagesTransformer $imagesIdsToImagesTransformer,
        ImageConfig $imageConfig
    ) {
        $this->imageFacade = $imageFacade;
        $this->imagesIdsToImagesTransformer = $imagesIdsToImagesTransformer;
        $this->imageConfig = $imageConfig;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ImageUploadData::class,
            'entity' => null,
            'image_type' => null,
            'multiple' => null,
            'image_entity_class' => null,
            'extensions' => ImageProcessor::SUPPORTED_EXTENSIONS,
        ]);

        $resolver->setNormalizer(
            'file_constraints',
            function (Options $options, $fileConstraints) {
                if ($options['extensions'] === null || $options['extensions'] === []) {
                    return $fileConstraints;
                }

                return array_merge(
                    [
                        new FileAllowedExtension(['extensions' => $options['extensions']]),
                    ],
                    $fileConstraints
                );
            }
        );
    }

    /**
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity'] = $options['entity'];
        $view->vars['images_by_id'] = $this->getImagesIndexedById($options);
        $view->vars['image_type'] = $options['image_type'];
        $view->vars['multiple'] = $this->isMultiple($options);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();

        $builder->add(
            $builder->create('orderedImages', CollectionType::class, [
                'required' => false,
                'entry_type' => HiddenType::class,
            ])->addModelTransformer($this->imagesIdsToImagesTransformer)
        );
        $builder->add('imagesToDelete', ChoiceType::class, [
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->getImagesIndexedById($options),
            'choice_label' => 'filename',
            'choice_value' => 'id',
        ])
            ->add('file', FileType::class, [
                'multiple' => $this->isMultiple($options),
                'mapped' => false,
                'attr' => [
                    'accept' => ImageProcessor::SUPPORTED_IMAGE_MIME_TYPES,
                ],
            ]);
    }

    /**
     * @param array $options
     * @return \App\Model\Image\Image[]
     */
    private function getImagesIndexedById(array $options)
    {
        if ($options['entity'] === null) {
            return [];
        }

        return $this->imageFacade->getImagesByEntityIndexedById($options['entity'], $options['image_type']);
    }

    /**
     * @param array $options
     * @return bool
     */
    private function isMultiple(array $options)
    {
        if ($options['multiple'] !== null) {
            return $options['multiple'];
        }

        if ($options['image_entity_class'] === null) {
            return false;
        }

        $imageEntityConfig = $this->imageConfig->getImageEntityConfigByClass($options['image_entity_class']);

        return $imageEntityConfig->isMultiple($options['image_type']);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return AbstractFileUploadType::class;
    }
}
