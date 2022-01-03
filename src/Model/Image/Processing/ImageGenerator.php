<?php

namespace App\Model\Image\Processing;

use League\Flysystem\FilesystemInterface;
use App\Model\Image\Config\ImageConfig;
use App\Model\Image\Image;
use App\Model\Image\ImageLocator;
use App\Model\Image\Processing\Exception\OriginalSizeImageCannotBeGeneratedException;

class ImageGenerator
{
    /**
     * @var \App\Model\Image\Processing\ImageProcessor
     */
    protected $imageProcessor;

    /**
     * @var \App\Model\Image\ImageLocator
     */
    protected $imageLocator;

    /**
     * @var \App\Model\Image\Config\ImageConfig
     */
    protected $imageConfig;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $filesystem;

    /**
     * @param \App\Model\Image\Processing\ImageProcessor $imageProcessor
     * @param \App\Model\Image\ImageLocator $imageLocator
     * @param \App\Model\Image\Config\ImageConfig $imageConfig
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(
        ImageProcessor $imageProcessor,
        ImageLocator $imageLocator,
        ImageConfig $imageConfig,
        FilesystemInterface $filesystem
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->imageLocator = $imageLocator;
        $this->imageConfig = $imageConfig;
        $this->filesystem = $filesystem;
    }

    /**
     * @param \App\Model\Image\Image $image
     * @param string|null $sizeName
     * @return string
     */
    public function generateImageSizeAndGetFilepath(Image $image, $sizeName)
    {
        $this->checkSizeNameIsNotOriginal($image, $sizeName);

        $sourceImageFilepath = $this->imageLocator->getAbsoluteImageFilepath($image, ImageConfig::ORIGINAL_SIZE_NAME);
        $targetImageFilepath = $this->imageLocator->getAbsoluteImageFilepath($image, $sizeName);
        $sizeConfig = $this->imageConfig->getImageSizeConfigByImage($image, $sizeName);

        $interventionImage = $this->imageProcessor->createInterventionImage($sourceImageFilepath);
        $this->imageProcessor->resizeBySizeConfig($interventionImage, $sizeConfig);

        $interventionImage->encode();

        $this->filesystem->put($targetImageFilepath, $interventionImage);

        return $targetImageFilepath;
    }

    /**
     * @param \App\Model\Image\Image $image
     * @param int $additionalIndex
     * @param string|null $sizeName
     * @return string
     */
    public function generateAdditionalImageSizeAndGetFilepath(Image $image, int $additionalIndex, ?string $sizeName)
    {
        $this->checkSizeNameIsNotOriginal($image, $sizeName);

        $sourceImageFilepath = $this->imageLocator->getAbsoluteImageFilepath($image, ImageConfig::ORIGINAL_SIZE_NAME);
        $targetImageFilepath = $this->imageLocator->getAbsoluteAdditionalImageFilepath(
            $image,
            $additionalIndex,
            $sizeName
        );
        $sizeConfig = $this->imageConfig->getImageSizeConfigByImage($image, $sizeName);
        $additionalSizeConfig = $sizeConfig->getAdditionalSize($additionalIndex);

        $interventionImage = $this->imageProcessor->createInterventionImage($sourceImageFilepath);
        $this->imageProcessor->resizeByAdditionalSizeConfig($interventionImage, $sizeConfig, $additionalSizeConfig);

        $interventionImage->encode();

        $this->filesystem->put($targetImageFilepath, $interventionImage);

        return $targetImageFilepath;
    }

    /**
     * @param \App\Model\Image\Image $image
     * @param string|null $sizeName
     */
    protected function checkSizeNameIsNotOriginal(Image $image, ?string $sizeName): void
    {
        if ($sizeName === ImageConfig::ORIGINAL_SIZE_NAME) {
            throw new OriginalSizeImageCannotBeGeneratedException($image);
        }
    }
}
