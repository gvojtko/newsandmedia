<?php

namespace App\Model\Image\Config\Exception;

use Exception;

class DuplicateSizeNameException extends Exception implements ImageConfigException
{
    /**
     * @var string|null
     */
    protected $sizeName;

    /**
     * @param string|null $sizeName
     * @param \Exception|null $previous
     */
    public function __construct($sizeName = null, ?Exception $previous = null)
    {
        $this->sizeName = $sizeName;

        if ($this->sizeName === null) {
            $message = 'Image size NULL is not unique.';
        } else {
            $message = sprintf('Image size "%s" is not unique.', $this->sizeName);
        }

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string|null
     */
    public function getSizeName()
    {
        return $this->sizeName;
    }
}
