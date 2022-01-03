<?php

namespace App\Component\Router\FriendlyUrl\Exception;

use Exception;
use App\Component\Router\FriendlyUrl\FriendlyUrl;

class ReachMaxUrlUniqueResolveAttemptException extends Exception implements FriendlyUrlException
{
    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrl $friendlyUrl
     * @param int $attempt
     * @param \Exception|null $previous
     */
    public function __construct(FriendlyUrl $friendlyUrl, $attempt, ?Exception $previous = null)
    {
        $message = 'Route "' . $friendlyUrl->getRouteName() . '" (param id = "' . $friendlyUrl->getEntityId() . '")'
            . ' reach max attempt (' . $attempt . ') for unique resolving.';

        parent::__construct($message, 0, $previous);
    }
}
