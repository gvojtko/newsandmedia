<?php

namespace App\Command;

use App\Component\Error\ErrorPagesFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateErrorPagesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:error-page:generate-all';

    /**
     * @var \App\Component\Error\ErrorPagesFacade
     */
    private $errorPagesFacade;

    /**
     * @param \App\Component\Error\ErrorPagesFacade $errorPagesFacade
     */
    public function __construct(ErrorPagesFacade $errorPagesFacade)
    {
        $this->errorPagesFacade = $errorPagesFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates all error pages for production.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->errorPagesFacade->generateAllErrorPagesForProduction();

        return CommandResultCodes::RESULT_OK;
    }
}
