<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\AuditLog;

use App\Entity\User;
use App\Vws\AuditLog\LoginAuditLogger;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginAuditLoggerTest extends MockeryTestCase
{
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private LoginAuditLogger $loginAuditLogger;
    private AuditLogger $auditLogger;

    public function setUp(): void
    {
        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->loginAuditLogger = new LoginAuditLogger(
            $this->auditLogger,
        );
    }

    public function testOnLogout(): void
    {
        $user = \Mockery::mock(User::class);

        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = \Mockery::mock(LogoutEvent::class);
        $event->expects('getToken')->andReturn($token);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (UserLogoutLogEvent $event) use ($user): bool {
                $this->assertEquals($user, $event->getActor());

                return true;
            }
        ));

        $this->loginAuditLogger->onLogout($event);
    }

    public function testOnFailureLogsMessage(): void
    {
        $exception = \Mockery::mock(AuthenticationException::class);
        $exception->shouldReceive('getMessageKey')->andReturn($key = 'key');
        $exception->shouldReceive('getMessageData')->andReturn($data = ['data']);

        $event = \Mockery::mock(LoginFailureEvent::class);
        $event->expects('getException')->andReturn($exception);
        $event->expects('getPassport->getUser');
        $event->expects('getPassport->getBadge->getPassword')->andReturn('foobar');
        $event->expects('getPassport->getBadge->getUserIdentifier')->andReturn('foo');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (UserLoginLogEvent $event): bool {
                $this->assertTrue($event->failed);
                $this->assertEquals(
                    [
                        'user_id' => 'foo',
                        'partial_password_hash' => 'c3ab8ff13720e8ad',
                        'exception_message_key' => 'key',
                        'exception_message_data' => ['data'],
                    ],
                    $event->data,
                );

                return true;
            }
        ));

        $this->loginAuditLogger->onAuthenticationFailure($event);
    }
}
