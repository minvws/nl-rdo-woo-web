<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class IndexAllSubjectsHandler
{
    public function __construct(
        private SubjectRepository $subjectRepository,
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function __invoke(IndexAllSubjectsCommand $command): void
    {
        foreach ($this->subjectRepository->getAllSubjectIdsWithLandingPageQuery()->toIterable() as $row) {
            Assert::isArray($row);
            Assert::keyExists($row, 'id');
            Assert::isInstanceOf($row['id'], Uuid::class);

            $this->searchDispatcher->dispatchIndexSubjectCommand($row['id']);
        }
    }
}
