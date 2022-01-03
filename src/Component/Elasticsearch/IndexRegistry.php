<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use App\Component\Elasticsearch\Exception\ElasticsearchIndexException;

class IndexRegistry
{
    /**
     * @var \App\Component\Elasticsearch\AbstractIndex[]
     */
    protected $registeredIndexes;

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex[] $indexes
     */
    public function __construct(iterable $indexes)
    {
        foreach ($indexes as $index) {
            $this->registerIndex($index);
        }
    }

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     */
    protected function registerIndex(AbstractIndex $index): void
    {
        $this->registeredIndexes[$index::getName()] = $index;
    }

    /**
     * @param string $indexName
     * @return bool
     */
    public function isIndexRegistered(string $indexName): bool
    {
        return array_key_exists($indexName, $this->registeredIndexes);
    }

    /**
     * @param string $indexName
     * @return \App\Component\Elasticsearch\AbstractIndex
     */
    public function getIndexByIndexName(string $indexName): AbstractIndex
    {
        if ($this->isIndexRegistered($indexName)) {
            return $this->registeredIndexes[$indexName];
        }

        throw ElasticsearchIndexException::noRegisteredIndexFound($indexName);
    }

    /**
     * @return string[]
     */
    public function getRegisteredIndexNames(): array
    {
        return array_keys($this->registeredIndexes);
    }

    /**
     * @return \App\Component\Elasticsearch\AbstractIndex[]
     */
    public function getRegisteredIndexes(): array
    {
        return $this->registeredIndexes;
    }
}
