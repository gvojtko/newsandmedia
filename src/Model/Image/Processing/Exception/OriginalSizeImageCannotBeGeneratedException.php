<?php

namespace App\Model\Image\Processing\Exception;

use Exception;
use App\Model\Image\Image;

class OriginalSizeImageCannotBeGeneratedException extends Exception implements ImageProcessingException
{
    /**
     * @param \App\Model\Image\Image $image
     * @param \Exception|null $previous
     */
    public function __construct(Image $image, ?Exception $previous = null)
    {
        $message = 'Original size of ' . $image->getFilename() . ' cannot be resized because it is original uploaded image.';

        parent::__construct($message, 0, $previous);
    }
}
