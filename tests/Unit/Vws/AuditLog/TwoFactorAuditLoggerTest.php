<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\AuditLog;

use App\Service\Security\User;
use App\Vws\AuditLog\TwoFactorAuditLogger;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLoginTwoFactorFailedEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorAuditLoggerTest extends MockeryTestCase
{
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private TwoFactorAuditLogger $twoFactorLogger;
    private AuditLogger $auditLogger;

    protected function setUp(): void
    {
        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->twoFactorLogger = new TwoFactorAuditLogger(
            $this->auditLogger,
        );
    }

    public function testOnSuccessLogsMessage(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getName')->andReturn('Foo Bar');
        $user->shouldReceive('getUserIdentifier')->andReturn('foo-123');
        $user->shouldReceive('getRoles')->andReturn(['FOO', 'BAR']);

        $request = \Mockery::mock(Request::class);
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new TwoFactorAuthenticationEvent(
            $request,
            $token,
        );

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (UserLoginLogEvent $event) use ($user): bool {
                $this->assertEquals($user, $event->getActor());

                return true;
            }
        ));

        $this->twoFactorLogger->onSuccess($event);
    }

    public function testOnFailureLogsMessage(): void
    {
        $user = \Mockery::mock(User::class);
        $request = \Mockery::mock(Request::class);
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new TwoFactorAuthenticationEvent(
            $request,
            $token,
        );

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (UserLoginTwoFactorFailedEvent $event) use ($user): bool {
                $this->assertEquals($user, $event->getActor());

                return true;
            }
        ));

        $this->twoFactorLogger->onFailure($event);
    }
}
