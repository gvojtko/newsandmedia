<?php

namespace App\Component\FileUpload;

class ImageUploadData
{
    /**
     * @var string[]
     */
    public $uploadedFiles = [];

    /**
     * @var string[]
     */
    public $uploadedFilenames = [];

    /**
     * @var \App\Component\Image\Image[]
     */
    public $imagesToDelete = [];

    /**
     * @var \App\Component\Image\Image[]
     */
    public $orderedImages = [];
}
