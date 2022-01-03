<?php

declare(strict_types=1);

namespace App\Component\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class DatabaseSchemaFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Component\Doctrine\Migrations\SchemaDiffFilter
     */
    protected $schemaDiffFilter;

    /**
     * @var \Doctrine\DBAL\Schema\Comparator
     */
    protected $comparator;

    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    protected $schemaTool;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Component\Doctrine\Migrations\SchemaDiffFilter $schemaDiffFilter
     * @param \Doctrine\DBAL\Schema\Comparator $comparator
     * @param \Doctrine\ORM\Tools\SchemaTool $schemaTool
     */
    public function __construct(
        EntityManagerInterface $em,
        SchemaDiffFilter $schemaDiffFilter,
        Comparator $comparator,
        SchemaTool $schemaTool
    ) {
        $this->em = $em;
        $this->schemaDiffFilter = $schemaDiffFilter;
        $this->comparator = $comparator;
        $this->schemaTool = $schemaTool;
    }

    /**
     * @return string[]
     */
    public function getFilteredSchemaDiffSqlCommands()
    {
        $allMetadata = $this->em->getMetadataFactory()->getAllMetadata();

        $databaseSchema = $this->em->getConnection()->getSchemaManager()->createSchema();
        $metadataSchema = $this->schemaTool->getSchemaFromMetadata($allMetadata);

        $schemaDiff = $this->comparator->compare($databaseSchema, $metadataSchema);
        $filteredSchemaDiff = $this->schemaDiffFilter->getFilteredSchemaDiff($schemaDiff);

        return $filteredSchemaDiff->toSaveSql($this->em->getConnection()->getDatabasePlatform());
    }
}
