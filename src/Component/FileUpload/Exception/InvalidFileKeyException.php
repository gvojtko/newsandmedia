<?php

namespace App\Component\FileUpload\Exception;

use Exception;
use App\Component\Utils\Debug;

class InvalidFileKeyException extends Exception implements FileUploadException
{
    /**
     * @param mixed $key
     * @param \Exception|null $previous
     */
    public function __construct($key, ?Exception $previous = null)
    {
        parent::__construct('Upload file key ' . Debug::export($key) . ' is invalid', 0, $previous);
    }
}
