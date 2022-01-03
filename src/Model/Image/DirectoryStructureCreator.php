<?php

namespace App\Model\Image;

use League\Flysystem\FilesystemInterface;
use App\Model\Image\Config\ImageConfig;

class DirectoryStructureCreator
{
    /**
     * @var \App\Model\Image\Config\ImageConfig
     */
    protected $imageConfig;

    /**
     * @var \App\Model\Image\ImageLocator
     */
    protected $imageLocator;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $imageDir;

    /**
     * @var string
     */
    protected $domainImageDir;

    /**
     * @param string $imageDir
     * @param string $domainImageDir
     * @param \App\Model\Image\Config\ImageConfig $imageConfig
     * @param \App\Model\Image\ImageLocator $imageLocator
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(
        $imageDir,
        $domainImageDir,
        ImageConfig $imageConfig,
        ImageLocator $imageLocator,
        FilesystemInterface $filesystem
    ) {
        $this->imageDir = $imageDir;
        $this->domainImageDir = $domainImageDir;
        $this->imageConfig = $imageConfig;
        $this->imageLocator = $imageLocator;
        $this->filesystem = $filesystem;
    }

    public function makeImageDirectories()
    {
        $imageEntityConfigs = $this->imageConfig->getAllImageEntityConfigsByClass();
        $directories = [];
        foreach ($imageEntityConfigs as $imageEntityConfig) {
            $sizeConfigs = $imageEntityConfig->getSizeConfigs();
            $sizesDirectories = $this->getTargetDirectoriesFromSizeConfigs(
                $imageEntityConfig->getEntityName(),
                null,
                $sizeConfigs
            );
            $directories = array_merge($directories, $sizesDirectories);

            foreach ($imageEntityConfig->getTypes() as $type) {
                $typeSizes = $imageEntityConfig->getSizeConfigsByType($type);
                $typeSizesDirectories = $this->getTargetDirectoriesFromSizeConfigs(
                    $imageEntityConfig->getEntityName(),
                    $type,
                    $typeSizes
                );
                $directories = array_merge($directories, $typeSizesDirectories);
            }
        }

        $directories[] = $this->domainImageDir;

        foreach ($directories as $directory) {
            $this->filesystem->createDir($directory);
        }
    }

    /**
     * @param string $entityName
     * @param string|null $type
     * @param \App\Model\Image\Config\ImageSizeConfig[] $sizeConfigs
     * @return string[]
     */
    protected function getTargetDirectoriesFromSizeConfigs($entityName, $type, array $sizeConfigs)
    {
        $directories = [];
        foreach ($sizeConfigs as $sizeConfig) {
            $relativePath = $this->imageLocator->getRelativeImagePath($entityName, $type, $sizeConfig->getName());
            $directories[] = $this->imageDir . $relativePath;
        }

        return $directories;
    }
}
