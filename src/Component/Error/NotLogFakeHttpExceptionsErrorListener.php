<?php

declare(strict_types=1);

namespace App\Component\Error;

use Psr\Log\LoggerInterface;
use App\Component\Error\Exception\FakeHttpException;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Throwable;

class NotLogFakeHttpExceptionsErrorListener extends ErrorListener
{
    /**
     * @var \App\Component\Error\ErrorIdProvider
     */
    protected $errorIdProvider;

    /**
     * @param mixed $controller
     * @param \App\Component\Error\ErrorIdProvider $errorIdProvider
     * @param \Psr\Log\LoggerInterface|null $logger
     * @param bool $debug
     */
    public function __construct($controller, ErrorIdProvider $errorIdProvider, ?LoggerInterface $logger = null, bool $debug = false)
    {
        parent::__construct($controller, $logger, $debug);

        $this->errorIdProvider = $errorIdProvider;
    }

    /**
     * @inheritDoc
     */
    protected function logException(Throwable $exception, $message): void
    {
        if (!$exception instanceof FakeHttpException) {
            $message .= sprintf(' Error ID: %s', $this->errorIdProvider->getErrorId());

            parent::logException($exception, $message);
        }
    }
}
