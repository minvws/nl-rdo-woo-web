<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
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

    public function create(string $name, Department $department): Organisation
    {
        $organisation = new Organisation();
        $organisation->setName($name);
        $organisation->setDepartment($department);

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
                'name' => $name,
                'department' => $department,
            ]));

        return $organisation;
    }

    public function update(Organisation $organisation, string $name, Department $department): Organisation
    {
        $oldOrganisation = clone $organisation;

        $organisation->setName($name);
        $organisation->setDepartment($department);

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
                    'name' => $oldOrganisation->getName(),
                    'department' => $oldOrganisation->getDepartment(),
                ],
                'new' => [
                    'name' => $organisation->getName(),
                    'department' => $organisation->getDepartment(),
                ],
            ]));

        return $organisation;
    }
}
