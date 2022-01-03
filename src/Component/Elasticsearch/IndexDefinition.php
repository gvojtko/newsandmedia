<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use App\Component\Elasticsearch\Exception\ElasticsearchIndexException;

class IndexDefinition
{
    const INDEX = 1;

    /**
     * @var string
     */
    protected $indexName;

    /**
     * @var \App\Component\Elasticsearch\AbstractIndex
     */
    protected $index;

    /**
     * @var string
     */
    protected $definitionsDirectory;

    /**
     * @var string
     */
    protected $indexPrefix;

    /**
     * @param string $indexName
     * @param string $definitionsDirectory
     * @param string $indexPrefix
     */
    public function __construct(string $indexName, string $definitionsDirectory, string $indexPrefix)
    {
        $this->indexName = $indexName;
        $this->definitionsDirectory = $definitionsDirectory;
        $this->indexPrefix = $indexPrefix;
    }

    /**
     * @return array
     */
    public function getDefinition(): array
    {
        $decodedDefinition = json_decode($this->getDefinitionFileContent(), true);
        if ($decodedDefinition === null) {
            throw ElasticsearchIndexException::invalidJsonInDefinitionFile(
                $this->getIndexName(),
                $this->getDefinitionFilepath()
            );
        }

        return $decodedDefinition;
    }

    /**
     * @return string
     */
    protected function getDefinitionFilepath(): string
    {
        return $this->definitionsDirectory . $this->getIndexName() . '/' . self::INDEX . '.json';
    }

    /**
     * @return string
     */
    protected function getDefinitionFileContent(): string
    {
        $definitionFilepath = $this->getDefinitionFilepath();
        if (!is_readable($definitionFilepath)) {
            throw ElasticsearchIndexException::cantReadDefinitionFile($definitionFilepath);
        }

        return file_get_contents($definitionFilepath);
    }

    /**
     * @return string
     */
    protected function getDocumentDefinitionVersion(): string
    {
        return md5(serialize($this->getDefinition()));
    }

    /**
     * @return string
     */
    public function getVersionedIndexName(): string
    {
        return sprintf('%s_%s', $this->getIndexAlias(), $this->getDocumentDefinitionVersion());
    }

    /**
     * @return string
     */
    public function getIndexAlias(): string
    {
        if ($this->indexPrefix === '') {
            return sprintf('%s', $this->getIndexName());
        }
        return sprintf('%s_%s', $this->indexPrefix, $this->getIndexName());
    }

    /**
     * @return string
     */
    public function getLegacyIndexAlias(): string
    {
        @trigger_error(
            sprintf('The %s() method is deprecated and will be removed in the next major.', __METHOD__),
            E_USER_DEPRECATED
        );

        return $this->indexPrefix . $this->getIndexName() ;
    }

    /**
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->indexName;
    }
}
