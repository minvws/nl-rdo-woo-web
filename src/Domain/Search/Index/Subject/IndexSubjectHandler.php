<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IndexSubjectHandler
{
    public function __construct(
        private SubjectRepository $subjectRepository,
        private SubjectIndexer $subjectIndexer,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(IndexSubjectCommand $command): void
    {
        try {
            $subject = $this->subjectRepository->find($command->getUuid());

            if (! $subject instanceof Subject) {
                $this->logger->warning('No subject entity found for IndexSubjectCommand', [
                    'uuid' => $command->getUuid(),
                ]);

                return;
            }

            if ($subject->getLandingPageSlug() === null) {
                $this->logger->warning('Subject has no landing-page slug for IndexSubjectCommand', [
                    'uuid' => $command->getUuid(),
                ]);

                return;
            }

            $this->subjectIndexer->index($subject);
        } catch (Exception $e) {
            $this->logger->error('Failed to update subject in elasticsearch', [
                'uuid' => $command->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
