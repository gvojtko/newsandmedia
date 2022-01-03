<?php

namespace App\Model\Image\Config;

use BadMethodCallException;
use App\Model\EntityExtension\EntityNameResolver;
use App\Model\Image\Config\Exception\ImageEntityConfigNotFoundException;
use App\Model\Image\Image;

class ImageConfig
{
    public const ORIGINAL_SIZE_NAME = 'original';
    public const DEFAULT_SIZE_NAME = 'default';

    /**
     * @var \App\Model\Image\Config\ImageEntityConfig[]
     */
    protected $imageEntityConfigsByClass;

    /**
     * @var \App\Model\EntityExtension\EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param \App\Model\Image\Config\ImageEntityConfig[] $imageEntityConfigsByClass
     * @param \App\Model\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(array $imageEntityConfigsByClass, ?EntityNameResolver $entityNameResolver = null)
    {
        $this->entityNameResolver = $entityNameResolver;
        if ($entityNameResolver !== null) {
            $this->setUpImageEntityConfigsByClass($imageEntityConfigsByClass);
        } else {
            $this->imageEntityConfigsByClass = $imageEntityConfigsByClass;
        }
    }

    /**
     * @param \App\Model\Image\Config\ImageEntityConfig[] $imageEntityConfigsByClass
     */
    protected function setUpImageEntityConfigsByClass(array $imageEntityConfigsByClass): void
    {
        $imageEntityConfigsByNormalizedClass = [];
        foreach ($imageEntityConfigsByClass as $class => $imageEntityConfig) {
            $normalizedClass = $this->entityNameResolver->resolve($class);
            $imageEntityConfigsByNormalizedClass[$normalizedClass] = $imageEntityConfig;
        }

        $this->imageEntityConfigsByClass = $imageEntityConfigsByNormalizedClass;
    }

    /**
     * @required
     * @internal This function will be replaced by constructor injection in next major
     * @param \App\Model\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function setEntityNameResolver(EntityNameResolver $entityNameResolver): void
    {
        if ($this->entityNameResolver !== null && $this->entityNameResolver !== $entityNameResolver) {
            throw new BadMethodCallException(sprintf(
                'Method "%s" has been already called and cannot be called multiple times.',
                __METHOD__
            ));
        }
        if ($this->entityNameResolver !== null) {
            return;
        }

        @trigger_error(
            sprintf(
                'The %s() method is deprecated and will be removed in the next major. Use the constructor injection instead.',
                __METHOD__
            ),
            E_USER_DEPRECATED
        );

        $this->entityNameResolver = $entityNameResolver;
        $this->setUpImageEntityConfigsByClass($this->imageEntityConfigsByClass);
    }

    /**
     * @param object $entity
     * @return string
     */
    public function getEntityName($entity)
    {
        $entityConfig = $this->getImageEntityConfig($entity);
        return $entityConfig->getEntityName();
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @param string|null $sizeName
     * @return \App\Model\Image\Config\ImageSizeConfig
     */
    public function getImageSizeConfigByEntity($entity, $type, $sizeName)
    {
        $entityConfig = $this->getImageEntityConfig($entity);
        return $entityConfig->getSizeConfigByType($type, $sizeName);
    }

    /**
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @return \App\Model\Image\Config\ImageSizeConfig
     */
    public function getImageSizeConfigByEntityName($entityName, $type, $sizeName)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entityName);
        return $entityConfig->getSizeConfigByType($type, $sizeName);
    }

    /**
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     */
    public function assertImageSizeConfigByEntityNameExists($entityName, $type, $sizeName)
    {
        $this->getImageSizeConfigByEntityName($entityName, $type, $sizeName);
    }

    /**
     * @param \App\Model\Image\Image $image
     * @param string|null $sizeName
     * @return \App\Model\Image\Config\ImageSizeConfig
     */
    public function getImageSizeConfigByImage(Image $image, $sizeName)
    {
        $entityConfig = $this->getEntityConfigByEntityName($image->getEntityName());
        return $entityConfig->getSizeConfigByType($image->getType(), $sizeName);
    }

    /**
     * @param object|null $entity
     * @return \App\Model\Image\Config\ImageEntityConfig
     */
    public function getImageEntityConfig($entity)
    {
        foreach ($this->imageEntityConfigsByClass as $className => $entityConfig) {
            if ($entity instanceof $className) {
                return $entityConfig;
            }
        }

        throw new ImageEntityConfigNotFoundException(
            $entity ? get_class($entity) : null
        );
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function hasImageConfig($entity)
    {
        foreach (array_keys($this->imageEntityConfigsByClass) as $className) {
            if ($entity instanceof $className) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $entityName
     * @return \App\Model\Image\Config\ImageEntityConfig
     */
    public function getEntityConfigByEntityName($entityName)
    {
        foreach ($this->imageEntityConfigsByClass as $entityConfig) {
            if ($entityConfig->getEntityName() === $entityName) {
                return $entityConfig;
            }
        }

        throw new ImageEntityConfigNotFoundException($entityName);
    }

    /**
     * @return \App\Model\Image\Config\ImageEntityConfig[]
     */
    public function getAllImageEntityConfigsByClass()
    {
        return $this->imageEntityConfigsByClass;
    }

    /**
     * @param string $class
     * @return \App\Model\Image\Config\ImageEntityConfig
     */
    public function getImageEntityConfigByClass($class)
    {
        $normalizedClass = $this->entityNameResolver->resolve($class);
        if (array_key_exists($normalizedClass, $this->imageEntityConfigsByClass)) {
            return $this->imageEntityConfigsByClass[$normalizedClass];
        }

        throw new ImageEntityConfigNotFoundException($class);
    }
}
