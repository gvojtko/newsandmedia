<?php

declare(strict_types=1);

namespace App\Component\Doctrine\Migrations;

use Doctrine\Migrations\Finder\Finder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;

class MigrationsFinder extends Finder
{
    /**
     * @var \Doctrine\Migrations\Finder\RecursiveRegexFinder
     */
    private $finder;

    /**
     * @var \App\Component\Doctrine\Migrations\MigrationsLocator
     */
    private $migrationsLocator;

    /**
     * @param \Doctrine\Migrations\Finder\RecursiveRegexFinder $finder
     * @param \App\Component\Doctrine\Migrations\MigrationsLocator $locator
     */
    public function __construct(RecursiveRegexFinder $finder, MigrationsLocator $locator)
    {
        $this->finder = $finder;
        $this->migrationsLocator = $locator;
    }

    /**
     * Finds all the migrations in all registered bundles using MigrationsLocator.
     * Passed parameters $directory and $namespace are ignored because of multiple sources of migrations.
     *
     * @param string $directory the passed $directory parameter is ignored
     * @param string|null $namespace the passed $namespace parameter is ignored
     * @return string[] an array of class names that were found with the version as keys
     */
    public function findMigrations(string $directory, ?string $namespace = null): array
    {
        $dir = $this->getRealPath($directory);

        $files = glob(rtrim($dir, '/') . '/Version*.php');

        return $this->loadMigrations($files, $namespace);
    }
}
