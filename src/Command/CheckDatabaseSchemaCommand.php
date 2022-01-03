<?php

namespace App\Command;

use App\Component\Doctrine\Migrations\DatabaseSchemaFacade;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckDatabaseSchemaCommand extends AbstractCommand
{
    private const RETURN_CODE_OK = 0;
    private const RETURN_CODE_ERROR = 1;

    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:migrations:check-schema';

    /**
     * @var \App\Component\Doctrine\Migrations\DatabaseSchemaFacade
     */
    private $databaseSchemaFacade;

    /**
     * @param \App\Component\Doctrine\Migrations\DatabaseSchemaFacade $databaseSchemaFacade
     */
    public function __construct(DatabaseSchemaFacade $databaseSchemaFacade)
    {
        $this->databaseSchemaFacade = $databaseSchemaFacade;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Check if database schema is satisfying ORM');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Checking database schema...');

        $filteredSchemaDiffSqlCommands = $this->databaseSchemaFacade->getFilteredSchemaDiffSqlCommands();
        if (count($filteredSchemaDiffSqlCommands) === 0) {
            $output->writeln('<info>Database schema is satisfying ORM.</info>');
            return self::RETURN_CODE_OK;
        }

        $output->writeln('<error>Database schema is not satisfying ORM!</error>');
        $output->writeln('<error>Following SQL commands should fix the problem (revise them before!):</error>');
        $output->writeln('');
        foreach ($filteredSchemaDiffSqlCommands as $sqlCommand) {
            $output->writeln('<fg=red>' . $sqlCommand . ';</fg=red>');
        }
        $output->writeln('<info>TIP: you can use newsandmedia:migrations:generate</info>');
        $output->writeln('');

        return self::RETURN_CODE_ERROR;
    }
}
