<?php

namespace App\Form\Transformers;

use App\Model\Image\Exception\ImageNotFoundException;
use App\Model\Image\ImageFacade;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ImagesIdsToImagesTransformer implements DataTransformerInterface
{
    /**
     * @var \App\Model\Image\ImageFacade
     */
    protected $imageFacade;

    /**
     * @param \App\Model\Image\ImageFacade $imageRepository
     */
    public function __construct(ImageFacade $imageRepository)
    {
        $this->imageFacade = $imageRepository;
    }

    /**
     * @param \App\Model\Image\Image[]|null $images
     * @return int[]
     */
    public function transform($images)
    {
        $imagesIds = [];

        if (is_iterable($images)) {
            foreach ($images as $image) {
                $imagesIds[] = $image->getId();
            }
        }

        return $imagesIds;
    }

    /**
     * @param int[] $imagesIds
     * @return \App\Model\Image\Image[]|null
     */
    public function reverseTransform($imagesIds)
    {
        $images = [];

        if (is_array($imagesIds)) {
            foreach ($imagesIds as $imageId) {
                try {
                    $images[] = $this->imageFacade->getById($imageId);
                } catch (ImageNotFoundException $e) {
                    throw new TransformationFailedException('Image not found', 0, $e);
                }
            }
        }

        return $images;
    }
}
