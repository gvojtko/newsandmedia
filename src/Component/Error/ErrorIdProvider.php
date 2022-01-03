<?php

declare(strict_types=1);

namespace App\Component\Error;

use App\Component\String\HashGenerator;

class ErrorIdProvider
{
    /**
     * @var \App\Component\String\HashGenerator
     */
    protected $hashGenerator;

    /**
     * @var string|null
     */
    protected $errorId;

    /**
     * @param \App\Component\String\HashGenerator $hashGenerator
     */
    public function __construct(HashGenerator $hashGenerator)
    {
        $this->hashGenerator = $hashGenerator;
    }

    /**
     * @return string
     */
    public function getErrorId(): string
    {
        if (!$this->errorId) {
            $this->errorId = $this->hashGenerator->generateHash(10);
        }
        return $this->errorId;
    }
}
