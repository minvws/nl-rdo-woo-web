<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Organisation;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\AuditUser;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\OrganisationChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\OrganisationCreatedLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This class handles organisation management.
 */
class OrganisationService
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected AuditLogger $auditLogger;
    protected TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $doctrine,
        LoggerInterface $logger,
        AuditLogger $auditLogger,
        TokenStorageInterface $tokenStorage
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->auditLogger = $auditLogger;
        $this->tokenStorage = $tokenStorage;
    }

    public function create(Organisation $organisation): void
    {
        $this->doctrine->persist($organisation);
        $this->doctrine->flush();

        $this->logger->log('info', 'Organisation created', [
            'organisation' => $organisation->getId(),
        ]);

        /** @var LoggableUser|null $loggedInUser */
        /** @phpstan-ignore-next-line */
        $loggedInUser = $this->tokenStorage->getToken()?->getUser() ?? null;
        if ($loggedInUser === null) {
            $loggedInUser = new AuditUser('cli user', 'system', [], 'system@localhost');
        }
        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new OrganisationCreatedLogEvent())
            ->asCreate()
            ->withActor($loggedInUser)
            ->withSource('woo')
            ->withData([
                'organisation_id' => $organisation->getId(),
                'name' => $organisation->getName(),
                'department' => $organisation->getDepartment(),
            ]));
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

        /** @var LoggableUser|null $loggedInUser */
        /** @phpstan-ignore-next-line */
        $loggedInUser = $this->tokenStorage->getToken()?->getUser() ?? null;
        if ($loggedInUser === null) {
            $loggedInUser = new AuditUser('cli user', 'system', [], 'system@localhost');
        }

        /** @var LoggableUser $loggedInUser */
        $this->auditLogger->log((new OrganisationChangeLogEvent())
            ->asUpdate()
            ->withActor($loggedInUser)
            ->withSource('woo')
            ->withData([
                'organisation_id' => $organisation->getId(),
            ])
            ->withPiiData([
                'old' => [
                    'name' => $changes['name'][0] ?? $organisation->getName(),
                    'department' => $changes['department'][0] ?? $organisation->getDepartment(),
                ],
                'new' => [
                    'name' => $changes['name'][1] ?? $organisation->getName(),
                    'department' => $changes['department'][1] ?? $organisation->getDepartment(),
                ],
            ]));

        return $organisation;
    }
}
