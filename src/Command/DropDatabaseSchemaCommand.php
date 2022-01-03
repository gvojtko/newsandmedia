<?php

namespace App\Command;

use App\Component\Doctrine\DatabaseSchemaFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DropDatabaseSchemaCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:schema:drop';

    /**
     * @var \App\Component\Doctrine\DatabaseSchemaFacade
     */
    private $databaseSchemaFacade;

    /**
     * @param \App\Component\Doctrine\DatabaseSchemaFacade $databaseSchemaFacade
     */
    public function __construct(DatabaseSchemaFacade $databaseSchemaFacade)
    {
        $this->databaseSchemaFacade = $databaseSchemaFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Drop database public schema');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Dropping database schema...');
        $this->databaseSchemaFacade->dropSchemaIfExists('public');
        $output->writeln('Database schema dropped successfully!');

        return CommandResultCodes::RESULT_OK;
    }
}
