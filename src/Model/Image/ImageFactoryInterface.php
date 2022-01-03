<?php

namespace App\Model\Image;

use App\Model\Image\Config\ImageEntityConfig;

interface ImageFactoryInterface
{
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
    ): Image;

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
    ): array;
}
