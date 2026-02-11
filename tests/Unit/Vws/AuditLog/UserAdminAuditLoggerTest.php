<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Vws\AuditLog;

use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserCreatedLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\Security\Event\UserCreatedEvent;
use Shared\Service\Security\Event\UserDisableEvent;
use Shared\Service\Security\Event\UserEnableEvent;
use Shared\Service\Security\Event\UserResetEvent;
use Shared\Service\Security\Event\UserUpdatedEvent;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\AuditLog\UserAdminAuditLogger;
use Spatie\Snapshots\MatchesSnapshots;

class UserAdminAuditLoggerTest extends UnitTestCase
{
    use MatchesSnapshots;

    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private UserAdminAuditLogger $userAdminAuditLogger;
    private AuditLogger $auditLogger;

    protected function setUp(): void
    {
        $this->internalAuditLogger = Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->userAdminAuditLogger = new UserAdminAuditLogger(
            $this->auditLogger,
        );
    }

    public function testOnCreated(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');
        $actor = Mockery::mock(User::class);
        $roles = ['FOO', 'BAR'];

        $this->internalAuditLogger->expects('log')->with(Mockery::on(
            static function (UserCreatedLogEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->target);
                self::assertEquals($actor, $event->actor);

                return true;
            }
        ));

        $this->userAdminAuditLogger->onCreated(new UserCreatedEvent($user, $actor, $roles));
    }

    public function testOnReset(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');
        $actor = Mockery::mock(User::class);

        $this->internalAuditLogger->expects('log')->with(Mockery::on(
            static function (ResetCredentialsLogEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->target);
                self::assertEquals($actor, $event->actor);

                return true;
            }
        ));

        $this->userAdminAuditLogger->onReset(new UserResetEvent($user, $actor, true, true));
    }

    public function testOnDisable(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');
        $actor = Mockery::mock(User::class);

        $this->internalAuditLogger->expects('log')->with(Mockery::on(
            static function (AccountChangeLogEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->target);
                self::assertEquals($actor, $event->actor);
                self::assertEquals(['user_id' => 'foo123', 'enabled' => false], $event->data);

                return true;
            }
        ));

        $this->userAdminAuditLogger->onDisable(new UserDisableEvent($user, $actor));
    }

    public function testOnEnable(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');
        $actor = Mockery::mock(User::class);

        $this->internalAuditLogger->expects('log')->with(Mockery::on(
            static function (AccountChangeLogEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->target);
                self::assertEquals($actor, $event->actor);
                self::assertEquals(['user_id' => 'foo123', 'enabled' => true], $event->data);

                return true;
            }
        ));

        $this->userAdminAuditLogger->onEnable(new UserEnableEvent($user, $actor));
    }

    public function testOnUpdate(): void
    {
        $oldUser = Mockery::mock(User::class);
        $oldUser->shouldReceive('getRoles')->andReturn(['BAR']);
        $oldUser->shouldReceive('getName')->andReturn('Bar');
        $oldUser->shouldReceive('getEmail')->andReturn('bar@foo.com');

        $updatedUser = Mockery::mock(User::class);
        $updatedUser->shouldReceive('getAuditId')->andReturn('foo123');
        $updatedUser->shouldReceive('getRoles')->andReturn(['FOO']);
        $updatedUser->shouldReceive('getName')->andReturn('Foo');
        $updatedUser->shouldReceive('getEmail')->andReturn('foo@bar.com');

        $actor = Mockery::mock(User::class);

        $this->internalAuditLogger->expects('log')->with(Mockery::on(
            function (AccountChangeLogEvent $event) use ($updatedUser, $actor): bool {
                self::assertEquals($updatedUser, $event->target);
                self::assertEquals($actor, $event->actor);
                $this->assertMatchesSnapshot($event->data);

                return true;
            }
        ));

        $this->userAdminAuditLogger->onUpdate(new UserUpdatedEvent($oldUser, $updatedUser, $actor));
    }
}
