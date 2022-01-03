<?php

declare(strict_types=1);

namespace App\Component\Elasticsearch;

use Symfony\Contracts\EventDispatcher\Event;

class IndexExportedEvent extends Event
{
    public const INDEX_EXPORTED = 'elasticsearch.index.exported';

    /**
     * @var \App\Component\Elasticsearch\AbstractIndex
     */
    protected $index;

    /**
     * @param \App\Component\Elasticsearch\AbstractIndex $index
     */
    public function __construct(AbstractIndex $index)
    {
        $this->index = $index;
    }

    /**
     * @return \App\Component\Elasticsearch\AbstractIndex
     */
    public function getIndex(): AbstractIndex
    {
        return $this->index;
    }
}
