<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

abstract class AbstractIndex
{
    public const BATCH_SIZE = 100;

    /**
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * @return int
     */
    abstract public function getTotalCount(): int;

    /**
     * @param array $restrictToIds
     * @return array
     */
    abstract public function getExportDataForIds(array $restrictToIds): array;

    /**
     * @param int $lastProcessedId
     * @param int $batchSize
     * @return array
     */
    abstract public function getExportDataForBatch(int $lastProcessedId, int $batchSize): array;

    /**
     * @return int
     */
    public function getExportBatchSize(): int
    {
        return static::BATCH_SIZE;
    }
}
