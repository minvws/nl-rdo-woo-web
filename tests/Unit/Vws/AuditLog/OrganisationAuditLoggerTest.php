<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\AuditLog;

use App\Domain\Department\Department;
use App\Domain\Organisation\Event\OrganisationCreatedEvent;
use App\Domain\Organisation\Event\OrganisationUpdatedEvent;
use App\Domain\Organisation\Organisation;
use App\Service\Security\User;
use App\Vws\AuditLog\OrganisationAuditLogger;
use Doctrine\Common\Collections\ArrayCollection;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\OrganisationChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\OrganisationCreatedLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Uid\Uuid;

class OrganisationAuditLoggerTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private OrganisationAuditLogger $organisationAuditLogger;
    private AuditLogger $auditLogger;

    protected function setUp(): void
    {
        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->organisationAuditLogger = new OrganisationAuditLogger(
            $this->auditLogger,
        );
    }

    public function testOnCreated(): void
    {
        $actor = \Mockery::mock(User::class);

        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn('department Foo');

        $organisation = \Mockery::mock(Organisation::class);
        $organisation->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1efe88cf-1e86-6a86-a022-dfa43a74a2ab'));
        $organisation->shouldReceive('getName')->andReturn('Foo');
        $organisation->shouldReceive('getDepartments')->andReturn(new ArrayCollection([
            $department,
        ]));

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (OrganisationCreatedLogEvent $event) use ($actor): bool {
                self::assertEquals($actor, $event->actor);
                $this->assertMatchesSnapshot($event->data);

                return true;
            }
        ));

        $this->organisationAuditLogger->onCreated(new OrganisationCreatedEvent($actor, $organisation));
    }

    public function testOnUpdated(): void
    {
        $actor = \Mockery::mock(User::class);

        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn('department Foo');

        $organisation = \Mockery::mock(Organisation::class);
        $organisation->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1efe88cf-1e86-6a86-a022-dfa43a74a2ab'));
        $organisation->shouldReceive('getName')->andReturn('Foo');
        $organisation->shouldReceive('getDepartments')->andReturn(new ArrayCollection([
            $department,
        ]));

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (OrganisationChangeLogEvent $event) use ($actor): bool {
                self::assertEquals($actor, $event->actor);
                $this->assertMatchesSnapshot($event->data);

                return true;
            }
        ));

        $changes = [
            'name' => [
                0 => 'foo old',
                1 => 'foo new',
            ],
        ];

        $this->organisationAuditLogger->onUpdated(new OrganisationUpdatedEvent($actor, $organisation, $changes));
    }
}
