<?php

declare(strict_types=1);

namespace App\Command;

use App\Component\Doctrine\Migrations\DatabaseSchemaFacade;
use App\Component\Doctrine\Migrations\MigrationsLocator;
use App\Component\Generator\MigrationsGenerator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class GenerateMigrationCommand extends AbstractCommand
{
    private const RETURN_CODE_OK = 0;
    private const RETURN_CODE_ERROR = 1;

    /**
     * @var string
     */
    protected static $defaultName = 'newsandmedia:migrations:generate';

    /**
     * @var \App\Component\Doctrine\DatabaseSchemaFacade
     */
    private $databaseSchemaFacade;

    /**
     * @var \App\Component\Generator\MigrationsGenerator
     */
    private $migrationsGenerator;

    /**
     * @var \App\Component\Doctrine\Migrations\MigrationsLocator
     */
    private $migrationsLocator;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $vendorDirectoryPath;

    /**
     * @param string $vendorDirectoryPath
     * @param \App\Component\Doctrine\DatabaseSchemaFacade $databaseSchemaFacade
     * @param \App\Component\Generator\MigrationsGenerator $migrationsGenerator
     * @param \App\Component\Doctrine\Migrations\MigrationsLocator $migrationsLocator
     * @param \Symfony\Component\HttpKernel\KernelInterface $kernel
     */
    public function __construct(
        $vendorDirectoryPath,
        DatabaseSchemaFacade $databaseSchemaFacade,
        MigrationsGenerator $migrationsGenerator,
        MigrationsLocator $migrationsLocator,
        KernelInterface $kernel
    ) {
        $this->databaseSchemaFacade = $databaseSchemaFacade;
        $this->migrationsGenerator = $migrationsGenerator;
        $this->migrationsLocator = $migrationsLocator;
        $this->kernel = $kernel;
        $this->vendorDirectoryPath = $vendorDirectoryPath;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate a new migration if need it');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Checking database schema...');

        $filteredSchemaDiffSqlCommands = $this->databaseSchemaFacade->getFilteredSchemaDiffSqlCommands();
        if (count($filteredSchemaDiffSqlCommands) === 0) {
            $output->writeln('<info>Database schema is satisfying ORM, no migrations were generated.</info>');

            return self::RETURN_CODE_OK;
        }

        $io = new SymfonyStyle($input, $output);

        $migrationsLocation = $this->chooseMigrationLocation($io);

        $generatorResult = $this->migrationsGenerator->generate(
            $filteredSchemaDiffSqlCommands,
            $migrationsLocation
        );

        if ($generatorResult->hasError()) {
            $output->writeln(
                '<error>Migration file "' . $generatorResult->getMigrationFilePath() . '" could not be saved.</error>'
            );

            return self::RETURN_CODE_ERROR;
        }

        $output->writeln('<info>Database schema is not satisfying ORM, a new migration was generated!</info>');
        $output->writeln(sprintf(
            '<info>Migration file "%s" was saved (%d B).</info>',
            $generatorResult->getMigrationFilePath(),
            $generatorResult->getWrittenBytes()
        ));

        return self::RETURN_CODE_OK;
    }

    /**
     * @param \Symfony\Component\Console\Style\SymfonyStyle $io
     * @return \App\Component\Doctrine\Migrations\MigrationsLocation
     */
    private function chooseMigrationLocation(SymfonyStyle $io)
    {
        $applicationMigrationLocation = $this->migrationsLocator->getApplicationMigrationLocation();
        $bundles = $this->getAllBundleNamesExceptVendor();
        array_unshift($bundles, $applicationMigrationLocation->getNamespace());

        if (count($bundles) > 1) {
            $chosenBundle = $io->choice(
                'There is more than one bundle available as the destination of generated migrations. Which bundle would you like to choose?',
                $bundles
            );
        } else {
            $chosenBundle = reset($bundles);
        }

        if ($chosenBundle === $applicationMigrationLocation->getNamespace()) {
            return $applicationMigrationLocation;
        }

        return $this->getMigrationLocation($this->kernel->getBundle($chosenBundle));
    }

    /**
     * @return string[]
     */
    private function getAllBundleNamesExceptVendor()
    {
        $bundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if (strpos(realpath($bundle->getPath()), realpath($this->vendorDirectoryPath)) !== 0) {
                $bundles[] = $bundle->getName();
            }
        }
        return $bundles;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle
     * @return \App\Component\Doctrine\Migrations\MigrationsLocation
     */
    private function getMigrationLocation(BundleInterface $bundle)
    {
        return $this->migrationsLocator->createMigrationsLocation($bundle);
    }
}
