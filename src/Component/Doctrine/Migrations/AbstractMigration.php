<?php

declare(strict_types=1);

namespace App\Component\Doctrine\Migrations;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Migrations\AbstractMigration as DoctrineAbstractMigration;

abstract class AbstractMigration extends DoctrineAbstractMigration
{
    /**
     * {@inheritDoc}
     * @throws \App\Component\Doctrine\Migrations\Exception\MethodIsNotAllowedException
     */
    protected function addSql($sql, array $params = [], array $types = []): void
    {
        $message = 'Calling method "addSql" is not allowed. Use "sql" method instead';
        throw new \App\Component\Doctrine\Migrations\Exception\MethodIsNotAllowedException($message);
    }

    /**
     * @param string $query
     * @param array $params
     * @param array $types
     * @param \Doctrine\DBAL\Cache\QueryCacheProfile|null $qcp
     * @return \Doctrine\DBAL\Driver\ResultStatement
     */
    public function sql($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
    {
        return $this->connection->executeQuery($query, $params, $types, $qcp);
    }

    /**
     * {@inheritDoc}
     *
     * @see \App\Command\MigrateCommand::execute()
     */
    public function isTransactional(): bool
    {
        // We do not want every migration to be executed in a separate transaction
        // because MigrateCommand wraps all migrations in a single transaction.
        return false;
    }
}
