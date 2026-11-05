<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Event;

use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SubjectDeletedHandler
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function __invoke(SubjectDeletedEvent $event): void
    {
        $this->searchDispatcher->dispatchDeleteElasticDocumentCommand(
            $event->getUuid()->toRfc4122(),
        );
    }
}
