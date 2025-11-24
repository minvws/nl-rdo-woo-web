<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Doctrine\ORM\Query;
use Shared\Domain\Publication\Subject\Event\SubjectUpdatedEvent;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class SubjectService
{
    public function __construct(
        private SubjectRepository $repository,
        private AuthorizationMatrix $authorizationMatrix,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function createNew(): Subject
    {
        $subject = new Subject();
        $subject->setOrganisation(
            $this->authorizationMatrix->getActiveOrganisation(),
        );

        return $subject;
    }

    public function saveNew(Subject $subject): void
    {
        $this->repository->save($subject, true);
    }

    public function save(Subject $subject): void
    {
        $this->repository->save($subject, true);

        $this->messageBus->dispatch(
            SubjectUpdatedEvent::forSubject($subject)
        );
    }

    public function getSubjectsQueryForActiveOrganisation(): Query
    {
        return $this->repository->getQueryForOrganisation(
            $this->authorizationMatrix->getActiveOrganisation(),
        );
    }
}
