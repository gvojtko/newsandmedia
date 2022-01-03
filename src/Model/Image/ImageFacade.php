<?php

declare(strict_types=1);

namespace App\Model\Image;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use App\Component\FileUpload\FileUpload;
use App\Component\FileUpload\ImageUploadData;
use App\Model\Image\Config\ImageConfig;
use App\Model\Image\Exception\EntityIdentifierException;
use App\Model\Image\Exception\ImageNotFoundException;
use App\Model\String\TransformString;

class ImageFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Image\Config\ImageConfig
     */
    protected $imageConfig;

    /**
     * @var \App\Model\Image\ImageRepository
     */
    protected $imageRepository;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var \League\Flysystem\MountManager
     */
    protected $mountManager;

    /**
     * @var \App\Component\FileUpload\FileUpload
     */
    protected $fileUpload;

    /**
     * @var \App\Model\Image\ImageLocator
     */
    protected $imageLocator;

    /**
     * @var string
     */
    protected $imageUrlPrefix;

    /**
     * @var \App\Model\Image\ImageFactoryInterface
     */
    protected $imageFactory;

    /**
     * @param mixed $imageUrlPrefix
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Image\Config\ImageConfig $imageConfig
     * @param \App\Model\Image\ImageRepository $imageRepository
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \App\Model\Image\ImageLocator $imageLocator
     * @param \App\Model\Image\ImageFactoryInterface $imageFactory
     * @param \League\Flysystem\MountManager $mountManager
     */
    public function __construct(
        $imageUrlPrefix,
        EntityManagerInterface $em,
        ImageConfig $imageConfig,
        ImageRepository $imageRepository,
        FilesystemInterface $filesystem,
        FileUpload $fileUpload,
        ImageLocator $imageLocator,
        ImageFactoryInterface $imageFactory,
        MountManager $mountManager
    ) {
        $this->imageUrlPrefix = $imageUrlPrefix;
        $this->em = $em;
        $this->imageConfig = $imageConfig;
        $this->imageRepository = $imageRepository;
        $this->filesystem = $filesystem;
        $this->fileUpload = $fileUpload;
        $this->imageLocator = $imageLocator;
        $this->imageFactory = $imageFactory;
        $this->mountManager = $mountManager;
    }

    /**
     * @param object $entity
     * @param \App\Component\FileUpload\ImageUploadData $imageUploadData
     * @param string|null $type
     */
    public function manageImages(object $entity, ImageUploadData $imageUploadData, ?string $type = null): void
    {
        $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
        $uploadedFiles = $imageUploadData->uploadedFiles;
        $orderedImages = $imageUploadData->orderedImages;

        if ($imageEntityConfig->isMultiple($type) === false) {
            if (count($orderedImages) > 1) {
                array_shift($orderedImages);
                $this->deleteImages($entity, $orderedImages);
            }
            $this->uploadImage($entity, $uploadedFiles, $type);
        } else {
            $this->saveImageOrdering($orderedImages);
            $this->uploadImages($entity, $uploadedFiles, $type);
        }

        $this->deleteImages($entity, $imageUploadData->imagesToDelete);
    }

    /**
     * @param object $entity
     * @param array $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImage($entity, $temporaryFilenames, $type): void
    {
        if (count($temporaryFilenames) > 0) {
            $entitiesForFlush = [];
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityId = $this->getEntityId($entity);
            $oldImage = $this->imageRepository->findImageByEntity(
                $imageEntityConfig->getEntityName(),
                $entityId,
                $type
            );

            if ($oldImage !== null) {
                $this->em->remove($oldImage);
                $entitiesForFlush[] = $oldImage;
            }

            $newImage = $this->imageFactory->create(
                $imageEntityConfig->getEntityName(),
                $entityId,
                $type,
                array_pop($temporaryFilenames)
            );
            $this->em->persist($newImage);
            $entitiesForFlush[] = $newImage;

            $this->em->flush();
        }
    }

    /**
     * @param \App\Model\Image\Image[] $orderedImages
     */
    protected function saveImageOrdering($orderedImages): void
    {
        $this->setImagePositionsByOrder($orderedImages);
        $this->em->flush();
    }

    /**
     * @param object $entity
     * @param array|null $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImages($entity, $temporaryFilenames, $type): void
    {
        if ($temporaryFilenames !== null && count($temporaryFilenames) > 0) {
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityId = $this->getEntityId($entity);

            $images = $this->imageFactory->createMultiple($imageEntityConfig, $entityId, $type, $temporaryFilenames);
            foreach ($images as $image) {
                $this->em->persist($image);
            }
            $this->em->flush();
        }
    }

    /**
     * @param object $entity
     * @param \App\Model\Image\Image[] $images
     */
    protected function deleteImages($entity, array $images): void
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        // files will be deleted in doctrine listener
        foreach ($images as $image) {
            $image->checkForDelete($entityName, $entityId);
        }

        foreach ($images as $image) {
            $this->em->remove($image);
        }
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Model\Image\Image
     */
    public function getImageByEntity($entity, $type)
    {
        return $this->imageRepository->getImageByEntity(
            $this->imageConfig->getEntityName($entity),
            $this->getEntityId($entity),
            $type
        );
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Model\Image\Image[]
     */
    public function getImagesByEntityIndexedById($entity, $type)
    {
        return $this->imageRepository->getImagesByEntityIndexedById(
            $this->imageConfig->getEntityName($entity),
            $this->getEntityId($entity),
            $type
        );
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param string|null $type
     * @return \App\Model\Image\Image[]
     */
    public function getImagesByEntityIdAndNameIndexedById(int $entityId, string $entityName, $type)
    {
        return $this->imageRepository->getImagesByEntityIndexedById(
            $entityName,
            $entityId,
            $type
        );
    }

    /**
     * @param object $entity
     * @return \App\Model\Image\Image[]
     */
    public function getAllImagesByEntity($entity)
    {
        return $this->imageRepository->getAllImagesByEntity(
            $this->imageConfig->getEntityName($entity),
            $this->getEntityId($entity)
        );
    }

    /**
     * @param \App\Model\Image\Image $image
     */
    public function deleteImageFiles(Image $image)
    {
        $entityName = $image->getEntityName();
        $imageConfig = $this->imageConfig->getEntityConfigByEntityName($entityName);
        $sizeConfigs = $image->getType() === null ? $imageConfig->getSizeConfigs() : $imageConfig->getSizeConfigsByType(
            $image->getType()
        );
        foreach ($sizeConfigs as $sizeConfig) {
            $filepath = $this->imageLocator->getAbsoluteImageFilepath($image, $sizeConfig->getName());

            if ($this->filesystem->has($filepath)) {
                $this->filesystem->delete($filepath);
            }
        }
    }

    /**
     * @param object $entity
     * @return int
     */
    protected function getEntityId($entity)
    {
        $entityMetadata = $this->em->getClassMetadata(get_class($entity));
        $identifier = $entityMetadata->getIdentifierValues($entity);
        if (count($identifier) === 1) {
            return array_pop($identifier);
        }

        $message = 'Entity "' . get_class($entity) . '" has not set primary key or primary key is compound."';
        throw new EntityIdentifierException($message);
    }

    /**
     * @return \App\Model\Image\Config\ImageEntityConfig[]
     */
    public function getAllImageEntityConfigsByClass()
    {
        return $this->imageConfig->getAllImageEntityConfigsByClass();
    }

    /**
     * @param \App\Model\Image\Image|object $imageOrEntity
     * @param string|null $sizeName
     * @param string|null $type
     * @return string
     */
    public function getImageUrl($imageOrEntity, $sizeName = null, $type = null)
    {
        $image = $this->getImageByObject($imageOrEntity, $type);
        if ($this->imageLocator->imageExists($image)) {
            return $this->getUrl()
                . $this->imageUrlPrefix
                . $this->imageLocator->getRelativeImageFilepath($image, $sizeName);
        }

        throw new ImageNotFoundException();
    }

    /**
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @return string
     */
    public function getImageUrlFromAttributes(
        int $id,
        string $extension,
        string $entityName,
        ?string $type,
        ?string $sizeName = null
    ): string {
        $imageFilepath = $this->imageLocator->getRelativeImageFilepathFromAttributes(
            $id,
            $extension,
            $entityName,
            $type,
            $sizeName
        );

        return $this->getUrl() . $this->imageUrlPrefix . $imageFilepath;
    }

    /**
     * @param \App\Model\Image\Image $imageOrEntity
     * @param string|null $sizeName
     * @param string|null $type
     * @return \App\Model\Image\AdditionalImageData[]
     */
    public function getAdditionalImagesData($imageOrEntity, ?string $sizeName, ?string $type)
    {
        $image = $this->getImageByObject($imageOrEntity, $type);

        $entityConfig = $this->imageConfig->getEntityConfigByEntityName($image->getEntityName());
        $sizeConfig = $entityConfig->getSizeConfigByType($type, $sizeName);

        $result = [];
        foreach ($sizeConfig->getAdditionalSizes() as $additionalSizeIndex => $additionalSizeConfig) {
            $url = $this->getAdditionalImageUrl($additionalSizeIndex, $image, $sizeName);
            $result[] = new AdditionalImageData($additionalSizeConfig->getMedia(), $url);
        }
        return $result;
    }

    /**
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @return \App\Model\Image\AdditionalImageData[]
     */
    public function getAdditionalImagesDataFromAttributes(
        int $id,
        string $extension,
        string $entityName,
        ?string $type,
        ?string $sizeName = null
    ): array {
        $entityConfig = $this->imageConfig->getEntityConfigByEntityName($entityName);
        $sizeConfig = $entityConfig->getSizeConfigByType($type, $sizeName);

        $result = [];
        foreach ($sizeConfig->getAdditionalSizes() as $additionalSizeIndex => $additionalSizeConfig) {
            $imageFilepath = $this->imageLocator->getRelativeImageFilepathFromAttributes(
                $id,
                $extension,
                $entityName,
                $type,
                $sizeName,
                $additionalSizeIndex
            );
            $url = $this->getUrl() . $this->imageUrlPrefix . $imageFilepath;

            $result[] = new AdditionalImageData($additionalSizeConfig->getMedia(), $url);
        }

        return $result;
    }

    /**
     * @param int $additionalSizeIndex
     * @param \App\Model\Image\Image $image
     * @param string|null $sizeName
     * @return string
     */
    protected function getAdditionalImageUrl(int $additionalSizeIndex, Image $image, ?string $sizeName)
    {
        if ($this->imageLocator->imageExists($image)) {
            return $this->getUrl()
                . $this->imageUrlPrefix
                . $this->imageLocator->getRelativeAdditionalImageFilepath($image, $additionalSizeIndex, $sizeName);
        }

        throw new ImageNotFoundException();
    }

    /**
     * @param \App\Model\Image\Image|object $imageOrEntity
     * @param string|null $type
     * @return \App\Model\Image\Image
     */
    public function getImageByObject($imageOrEntity, $type = null)
    {
        if ($imageOrEntity instanceof Image) {
            return $imageOrEntity;
        }
        return $this->getImageByEntity($imageOrEntity, $type);
    }

    /**
     * @param int $imageId
     * @return \App\Model\Image\Image
     */
    public function getById($imageId)
    {
        return $this->imageRepository->getById($imageId);
    }

    /**
     * @param object $sourceEntity
     * @param object $targetEntity
     */
    public function copyImages($sourceEntity, $targetEntity)
    {
        $sourceImages = $this->getAllImagesByEntity($sourceEntity);
        $targetImages = [];
        foreach ($sourceImages as $sourceImage) {
            $this->mountManager->copy(
                'main://' . $this->imageLocator->getAbsoluteImageFilepath(
                    $sourceImage,
                    ImageConfig::ORIGINAL_SIZE_NAME
                ),
                'main://' . TransformString::removeDriveLetterFromPath(
                    $this->fileUpload->getTemporaryFilepath($sourceImage->getFilename())
                )
            );

            $targetImage = $this->imageFactory->create(
                $this->imageConfig->getImageEntityConfig($targetEntity)->getEntityName(),
                $this->getEntityId($targetEntity),
                $sourceImage->getType(),
                $sourceImage->getFilename()
            );

            $this->em->persist($targetImage);
            $targetImages[] = $targetImage;
        }
        $this->em->flush();
    }

    /**
     * @param \App\Model\Image\Image[] $orderedImages
     */
    protected function setImagePositionsByOrder($orderedImages)
    {
        $position = 0;
        foreach ($orderedImages as $image) {
            $image->setPosition($position);
            $position++;
        }
    }

    /**
     * @param int[] $entityIds
     * @param string $entityClass FQCN
     * @return \App\Model\Image\Image[]
     */
    public function getImagesByEntitiesIndexedByEntityId(array $entityIds, string $entityClass): array
    {
        $entityName = $this->imageConfig->getImageEntityConfigByClass($entityClass)->getEntityName();

        return $this->imageRepository->getMainImagesByEntitiesIndexedByEntityId($entityIds, $entityName);
    }

    /**
     * @param int $id
     * @param string $entityClass
     * @return \App\Model\Image\Image[]
     */
    public function getImagesByEntityId(int $id, string $entityClass): array
    {
        $entityName = $this->imageConfig->getImageEntityConfigByClass($entityClass)->getEntityName();

        return $this->getImagesByEntityIdAndNameIndexedById($id, $entityName, null);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->container->getParameter('domain_url');
    }
}
