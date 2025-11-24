<?php

declare(strict_types=1);

namespace Shared\Vws\AuditLog;

use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\OrganisationChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\OrganisationCreatedLogEvent;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Event\OrganisationCreatedEvent;
use Shared\Domain\Organisation\Event\OrganisationUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class OrganisationAuditLogger
{
    private const string UNCHANGED = '[unchanged]';

    public function __construct(
        private AuditLogger $auditLogger,
    ) {
    }

    #[AsEventListener]
    public function onCreated(OrganisationCreatedEvent $event): void
    {
        $this->auditLogger->log((new OrganisationCreatedLogEvent())
            ->asCreate()
            ->withActor($event->actor)
            ->withSource('woo')
            ->withData([
                'organisation_id' => $event->organisation->getId(),
                'name' => $event->organisation->getName(),
                'departments' => $event->organisation->getDepartments()->map(
                    static fn (Department $department) => $department->getName(),
                ),
            ]));
    }

    #[AsEventListener]
    public function onUpdated(OrganisationUpdatedEvent $event): void
    {
        $this->auditLogger->log((new OrganisationChangeLogEvent())
            ->asUpdate()
            ->withActor($event->actor)
            ->withSource('woo')
            ->withData([
                'organisation_id' => $event->organisation->getId(),
            ])
            ->withPiiData([
                'old' => [
                    'name' => $event->changes['name'][0] ?? self::UNCHANGED,
                    'departments' => $event->changes['departments'][0] ?? self::UNCHANGED,
                ],
                'new' => [
                    'name' => $event->changes['name'][1] ?? self::UNCHANGED,
                    'departments' => $event->changes['departments'][1] ?? self::UNCHANGED,
                ],
            ]));
    }
}
