<?php

namespace App\Component\Error;

use App\Component\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class ErrorPageCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \App\Component\Error\ErrorPagesFacade
     */
    protected $errorPagesFacade;

    /**
     * @param \App\Component\Error\ErrorPagesFacade $errorPagesFacade
     */
    public function __construct(ErrorPagesFacade $errorPagesFacade)
    {
        $this->errorPagesFacade = $errorPagesFacade;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $this->errorPagesFacade->generateAllErrorPagesForProduction();
    }
}
