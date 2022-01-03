<?php

declare(strict_types=1);

namespace App\Component\Doctrine\Migrations;

use App\Component\Doctrine\Migrations\Exception\MethodIsNotAllowedException;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration as DoctrineConfiguration;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\QueryWriter;
use Doctrine\Migrations\Version\Version;

/**
 * @method string[] getMigratedVersions()
 * @see https://github.com/doctrine/migrations/pull/824
 */
class Configuration extends DoctrineConfiguration
{
    /**
     * @var \App\Component\Doctrine\Migrations\MigrationsLock
     */
    private $migrationsLock;

    /**
     * @var \Doctrine\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * @var \Doctrine\Migrations\Version\Version[]
     */
    private $migrationVersions = null;

    /**
     * @param \App\Component\Doctrine\Migrations\MigrationsLock $migrationsLock
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Doctrine\Migrations\OutputWriter $outputWriter
     * @param \Doctrine\Migrations\Finder\MigrationFinder $finder
     * @param \Doctrine\Migrations\QueryWriter|null $queryWriter
     */
    public function __construct(
        MigrationsLock $migrationsLock,
        Connection $connection,
        OutputWriter $outputWriter,
        MigrationFinder $finder,
        ?QueryWriter $queryWriter = null
    ) {
        $this->migrationsLock = $migrationsLock;
        $this->outputWriter = $outputWriter;

        parent::__construct($connection, $outputWriter, $finder, $queryWriter);
    }

    /**
     * Gets the array of registered migration versions filtered and ordered by information in the migrations lock.
     * Version number is used as an index, because \Doctrine\DBAL\Migrations\Migration::migrate depends on it.
     * The internal parent::$migrations variable contains all registered migrations (even skipped ones) ordered by the timestamp.
     *
     * @return \Doctrine\Migrations\Version\Version[] $migrations
     */
    public function getMigrations(): array
    {
        if ($this->migrationVersions === null) {
            $this->migrationVersions = [];

            $foundMigrationVersionsByClass = [];
            /* @var $foundMigrationVersionsByClass \Doctrine\Migrations\Version\Version[] */
            foreach (parent::getMigrations() as $migrationVersion) {
                $class = get_class($migrationVersion->getMigration());
                $foundMigrationVersionsByClass[$class] = $migrationVersion;
            }

            foreach ($this->migrationsLock->getSkippedMigrationClasses() as $skippedMigrationClass) {
                if (array_key_exists($skippedMigrationClass, $foundMigrationVersionsByClass)) {
                    unset($foundMigrationVersionsByClass[$skippedMigrationClass]);
                } else {
                    $message = sprintf('WARNING: Migration version "%s" marked as skipped in migration lock file was not found!', $skippedMigrationClass);
                    $this->outputWriter->write($message);
                }
            }

            foreach ($this->migrationsLock->getOrderedInstalledMigrationClasses() as $installedMigrationClass) {
                if (array_key_exists($installedMigrationClass, $foundMigrationVersionsByClass)) {
                    $installedMigrationVersion = $foundMigrationVersionsByClass[$installedMigrationClass];
                    $this->migrationVersions[$installedMigrationVersion->getVersion()] = $installedMigrationVersion;
                    unset($migrationVersion);
                } else {
                    $message = sprintf('WARNING: Migration version "%s" marked as installed in migration lock file was not found!', $installedMigrationClass);
                    $this->outputWriter->write($message);
                }
            }

            foreach ($foundMigrationVersionsByClass as $newMigrationVersion) {
                $this->migrationVersions[$newMigrationVersion->getVersion()] = $newMigrationVersion;
            }
        }

        return $this->migrationVersions;
    }

    /**
     * Returns the array of migrations to executed based on the given direction and target version number.
     * Because of multiple migrations locations and the lock file, only complete UP migrations are allowed.
     *
     * @param string $direction the direction we are migrating (DOWN is not allowed)
     * @param string $to the version to migrate to (partial migrations are not allowed)
     *
     * @throws \App\Component\Doctrine\Migrations\Exception\MethodIsNotAllowedException
     * @return \Doctrine\Migrations\Version\Version[] $migrations the array of migrations we can execute
     */
    public function getMigrationsToExecute(string $direction, string $to): array
    {
        // TODO: Version::DIRECTION_DOWN not found
//        if ($direction === Version::DIRECTION_DOWN) {
//            $this->throwMethodIsNotAllowedException('Migration down is not allowed.');
//        }

        $migrationVersionsToExecute = [];
        $allMigrationVersions = $this->getMigrations();
        $migratedVersions = $this->getMigratedVersions();

        foreach ($allMigrationVersions as $version) {
            if ($to < $version->getVersion()) {
                $this->throwMethodIsNotAllowedException('Partial migration up in not allowed.');
            }

            if ($this->shouldExecuteMigration($version, $migratedVersions)) {
                $migrationVersionsToExecute[$version->getVersion()] = $version;
            }
        }

        return $migrationVersionsToExecute;
    }

    /**
     * @param \Doctrine\Migrations\Version\Version $version
     * @param string[] $migratedVersions
     * @return bool
     */
    private function shouldExecuteMigration(Version $version, array $migratedVersions)
    {
        return !in_array($version->getVersion(), $migratedVersions, true);
    }

    /**
     * @param string $message
     * @throws \App\Component\Doctrine\Migrations\Exception\MethodIsNotAllowedException
     */
    private function throwMethodIsNotAllowedException(string $message): void
    {
        $message .= ' Only up migration of all registered versions is supported because of multiple sources of migrations.';

        throw new MethodIsNotAllowedException($message);
    }
}
