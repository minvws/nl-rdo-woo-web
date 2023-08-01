<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Search\Result\Result;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber will trigger when the paginator is used.
 * It will check if the target of the paginator is a search result object, and if so,
 * we don't really need to slice the results, we can just use the results as-is, BUT
 * we need to update the count of the paginator to the total number of documents found.
 *
 * This listener is needed because the paginator by default cannot handle already sliced
 * objects in combination with the item count of the paginator.
 */
class PaginationCountSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => 'itemCount',
        ];
    }

    public function itemCount(ItemsEvent $event): void
    {
        if (! $event->target instanceof Result) {
            return;
        }

        $event->count = $event->target->getDocumentCount();
        $event->items = $event->target->getEntries();

        $event->stopPropagation();
    }
}
