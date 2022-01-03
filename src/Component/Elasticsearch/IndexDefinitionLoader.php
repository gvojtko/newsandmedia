<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

class IndexDefinitionLoader
{
    /**
     * @var string
     */
    protected $indexDefinitionsDirectory;

    /**
     * @var string
     */
    protected $indexPrefix;

    /**
     * @param string $indexDefinitionsDirectory
     * @param string $indexPrefix
     */
    public function __construct(string $indexDefinitionsDirectory, string $indexPrefix)
    {
        $this->indexDefinitionsDirectory = $indexDefinitionsDirectory;
        $this->indexPrefix = $indexPrefix;
    }

    /**
     * @param string $indexName
     * @return \App\Component\Elasticsearch\IndexDefinition
     */
    public function getIndexDefinition(string $indexName): IndexDefinition
    {
        return new IndexDefinition($indexName, $this->indexDefinitionsDirectory, $this->indexPrefix);
    }
}
