<?php

declare(strict_types=1);

namespace App\Domain\Publication\Subject\Event;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Publication\Subject\SubjectRepository;
use App\Domain\Search\Index\Updater\SubjectIndexUpdater;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class SubjectUpdatedHandler
{
    public function __construct(
        private SubjectRepository $repository,
        private SubjectIndexUpdater $indexUpdater,
    ) {
    }

    public function __invoke(SubjectUpdatedEvent $event): void
    {
        $subject = $this->repository->find($event->getUuid());
        Assert::isInstanceOf($subject, Subject::class);

        $this->indexUpdater->update($subject);
    }
}
