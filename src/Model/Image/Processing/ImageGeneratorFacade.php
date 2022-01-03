<?php

namespace App\Model\Image\Processing;

use App\Model\Image\Exception\ImageNotFoundException;
use App\Model\Image\Image;
use App\Model\Image\ImageRepository;

class ImageGeneratorFacade
{
    /**
     * @var \App\Model\Image\ImageRepository
     */
    protected $imageRepository;

    /**
     * @var \App\Model\Image\Processing\ImageGenerator
     */
    protected $imageGenerator;

    /**
     * @param \App\Model\Image\ImageRepository $imageRepository
     * @param \App\Model\Image\Processing\ImageGenerator $imageGenerator
     */
    public function __construct(
        ImageRepository $imageRepository,
        ImageGenerator $imageGenerator
    ) {
        $this->imageRepository = $imageRepository;
        $this->imageGenerator = $imageGenerator;
    }

    /**
     * @param string $entityName
     * @param int $imageId
     * @param string|null $type
     * @param string|null $sizeName
     * @return string
     */
    public function generateImageAndGetFilepath($entityName, $imageId, $type, $sizeName)
    {
        $image = $this->imageRepository->getById($imageId);

        $this->checkEntityNameAndType($image, $entityName, $type);

        return $this->imageGenerator->generateImageSizeAndGetFilepath($image, $sizeName);
    }

    /**
     * @param \App\Model\Image\Image $image
     * @param string $entityName
     * @param string|null $type
     */
    protected function checkEntityNameAndType(Image $image, string $entityName, ?string $type): void
    {
        if ($image->getEntityName() !== $entityName) {
            $message = sprintf('Image (ID = %s) does not have entity name "%s"', $image->getId(), $entityName);
            throw new ImageNotFoundException($message);
        }

        if ($image->getType() !== $type) {
            $message = sprintf('Image (ID = %s) does not have type "%s"', $image->getId(), $type);
            throw new ImageNotFoundException($message);
        }
    }

    /**
     * @param string $entityName
     * @param int $imageId
     * @param int $additionalIndex
     * @param string|null $type
     * @param string|null $sizeName
     * @return string
     */
    public function generateAdditionalImageAndGetFilepath(string $entityName, int $imageId, int $additionalIndex, ?string $type, ?string $sizeName): string
    {
        $image = $this->imageRepository->getById($imageId);

        $this->checkEntityNameAndType($image, $entityName, $type);

        return $this->imageGenerator->generateAdditionalImageSizeAndGetFilepath($image, $additionalIndex, $sizeName);
    }
}
