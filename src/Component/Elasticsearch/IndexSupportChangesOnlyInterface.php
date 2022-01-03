<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

interface IndexSupportChangesOnlyInterface
{
    /**
     * @return int
     */
    public function getChangedCount(): int;

    /**
     * @param int $lastProcessedId
     * @param int $batchSize
     * @return int[]
     */
    public function getChangedIdsForBatch(int $lastProcessedId, int $batchSize): array;
}
