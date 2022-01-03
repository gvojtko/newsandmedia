<?php

namespace App\Model\Image;

use App\Model\EntityExtension\EntityNameResolver;
use App\Component\FileUpload\FileUpload;
use App\Model\Image\Config\ImageEntityConfig;
use App\Model\Image\Exception\EntityMultipleImageException;
use App\Model\Image\Processing\ImageProcessor;

class ImageFactory implements ImageFactoryInterface
{
    /**
     * @var \App\Model\Image\Processing\ImageProcessor
     */
    protected $imageProcessor;

    /**
     * @var \App\Component\FileUpload\FileUpload
     */
    protected $fileUpload;

    /**
     * @var \App\Model\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \App\Model\Image\Processing\ImageProcessor $imageProcessor
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \App\Model\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ImageProcessor $imageProcessor,
        FileUpload $fileUpload,
        EntityNameResolver $entityNameResolver
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->fileUpload = $fileUpload;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param string|null $type
     * @param string $temporaryFilename
     * @return \App\Model\Image\Image
     */
    public function create(
        string $entityName,
        int $entityId,
        ?string $type,
        string $temporaryFilename
    ): Image {
        $temporaryFilePath = $this->fileUpload->getTemporaryFilepath($temporaryFilename);
        $convertedFilePath = $this->imageProcessor->convertToShopFormatAndGetNewFilename($temporaryFilePath);

        $classData = $this->entityNameResolver->resolve(Image::class);

        return new $classData($entityName, $entityId, $type, $convertedFilePath);
    }

    /**
     * @param \App\Model\Image\Config\ImageEntityConfig $imageEntityConfig
     * @param int $entityId
     * @param string|null $type
     * @param array $temporaryFilenames
     * @return \App\Model\Image\Image[]
     */
    public function createMultiple(
        ImageEntityConfig $imageEntityConfig,
        int $entityId,
        ?string $type,
        array $temporaryFilenames
    ): array {
        if (!$imageEntityConfig->isMultiple($type)) {
            $message = 'Entity ' . $imageEntityConfig->getEntityClass()
                . ' is not allowed to have multiple images for type ' . ($type ?: 'NULL');
            throw new EntityMultipleImageException($message);
        }

        $images = [];
        foreach ($temporaryFilenames as $temporaryFilename) {
            $images[] = $this->create($imageEntityConfig->getEntityName(), $entityId, $type, $temporaryFilename);
        }

        return $images;
    }
}
