<?php

namespace App\Component\Error\Exception;

use Exception;

class ErrorPageNotFoundException extends Exception implements ErrorException
{
    /**
     * @param int $statusCode
     * @param \Exception|null $previous
     */
    public function __construct($statusCode, ?Exception $previous = null)
    {
        $message = 'Error page with status code "' . $statusCode . ' not found.';

        parent::__construct($message, 0, $previous);
    }
}
