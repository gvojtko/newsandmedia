<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Component\Elasticsearch\IndexExportedEvent;
use App\Model\Product\ProductFacade;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MarkProductForExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(ProductFacade $productFacade)
    {
        $this->productFacade = $productFacade;
    }

    /**
     * @param \Symfony\Contracts\EventDispatcher\Event $event
     */
    public function markAll(Event $event): void
    {
        //$this->productFacade->markAllProductsForExport();
    }

    /**
     * @param \App\Component\Elasticsearch\IndexExportedEvent $indexExportedEvent
     */
    public function markAllAsExported(IndexExportedEvent $indexExportedEvent): void
    {
        if ($indexExportedEvent->getIndex() instanceof ProductIndex) {
            //$this->productFacade->markAllProductsAsExported();
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
//            ParameterEvent::DELETE => 'markAffectedByParameter',
//            PricingGroupEvent::CREATE => 'markAll',
//            PricingGroupEvent::DELETE => 'markAll',
            IndexExportedEvent::INDEX_EXPORTED => 'markAllAsExported',
        ];
    }
}
