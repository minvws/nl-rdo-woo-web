<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Organisation\Event\OrganisationCreatedEvent;
use App\Domain\Organisation\Event\OrganisationUpdatedEvent;
use App\Domain\Organisation\Organisation;
use App\Service\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditUser;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

readonly class OrganisationService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function create(Organisation $organisation): void
    {
        $this->doctrine->persist($organisation);
        $this->doctrine->flush();

        $this->logger->log('info', 'Organisation created', [
            'organisation' => $organisation->getId(),
        ]);

        $this->eventDispatcher->dispatch(
            new OrganisationCreatedEvent(
                actor: $this->getLoggableUser(),
                organisation: $organisation,
            ),
        );
    }

    public function update(Organisation $organisation): Organisation
    {
        $this->doctrine->getUnitOfWork()->computeChangeSets();
        $changes = $this->doctrine->getUnitOfWork()->getEntityChangeSet($organisation);

        $this->doctrine->persist($organisation);
        $this->doctrine->flush();

        $this->logger->log('info', 'Organisation updated', [
            'organisation' => $organisation->getId(),
        ]);

        $this->eventDispatcher->dispatch(
            new OrganisationUpdatedEvent(
                actor: $this->getLoggableUser(),
                organisation: $organisation,
                changes: $changes,
            ),
        );

        return $organisation;
    }

    private function getLoggableUser(): LoggableUser
    {
        /** @var User|null $loggedInUser */
        $loggedInUser = $this->tokenStorage->getToken()?->getUser() ?? null;
        if ($loggedInUser === null) {
            $loggedInUser = new AuditUser('cli user', 'system', [], 'system@localhost');
        }

        return $loggedInUser;
    }
}
