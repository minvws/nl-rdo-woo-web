<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Vws\AuditLog;

use Exception;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\AuditLog\LoginAuditLogger;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginAuditLoggerTest extends UnitTestCase
{
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private LoginAuditLogger $loginAuditLogger;

    protected function setUp(): void
    {
        $this->internalAuditLogger = Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->expects('canHandleEvent')
            ->andReturnTrue();
        $auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->loginAuditLogger = new LoginAuditLogger($auditLogger);
    }

    public function testOnLogout(): void
    {
        $user = Mockery::mock(User::class);

        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')
            ->andReturn($user);

        $event = Mockery::mock(LogoutEvent::class);
        $event->expects('getToken')
            ->andReturn($token);

        $this->internalAuditLogger->expects('log')
            ->with(Mockery::on(static function (UserLogoutLogEvent $event) use ($user): bool {
                self::assertEquals($user, $event->getActor());

                return true;
            }));

        $this->loginAuditLogger->onLogout($event);
    }

    public function testOnFailureLogsMessage(): void
    {
        $exception = Mockery::mock(AuthenticationException::class);
        $exception->expects('getMessageKey')
            ->andReturn('key');
        $exception->expects('getMessageData')
            ->andReturn(['data']);

        $event = Mockery::mock(LoginFailureEvent::class);
        $event->expects('getException')
            ->andReturn($exception);
        $event->expects('getPassport->getUser');
        $event->expects('getPassport->getBadge->getPassword')
            ->andReturn('foobar');
        $event->expects('getPassport->getBadge->getUserIdentifier')
            ->andReturn('foo');

        $this->internalAuditLogger->expects('log')
            ->with(Mockery::on(static function (UserLoginLogEvent $event): bool {
                self::assertTrue($event->failed);
                self::assertEquals('invalid_password', $event->failedReason);
                self::assertEquals(
                    [
                        'user_id' => 'foo',
                        'partial_password_hash' => 'c3ab8ff13720e8ad',
                        'exception_message_key' => 'key',
                        'exception_message_data' => ['data'],
                    ],
                    $event->data,
                );

                return true;
            }));

        $this->loginAuditLogger->onAuthenticationFailure($event);
    }

    public function testOnFailureWithEmailInvalid(): void
    {
        $exception = Mockery::mock(AuthenticationException::class);
        $exception->expects('getMessageKey')
            ->andReturn('key');
        $exception->shouldReceive('getMessageData')
            ->andReturn(['data']);

        $event = Mockery::mock(LoginFailureEvent::class);
        $event->expects('getException')
            ->andReturn($exception);
        $event->expects('getPassport->getUser')
            ->andThrow(new Exception());
        $event->expects('getPassport->getBadge->getPassword')
            ->andReturn('foobar');
        $event->expects('getPassport->getBadge->getUserIdentifier')
            ->andReturn('foo');

        $this->internalAuditLogger->expects('log')
            ->with(Mockery::on(static function (UserLoginLogEvent $event): bool {
                self::assertTrue($event->failed);
                self::assertEquals('invalid_email', $event->failedReason);
                self::assertEquals(
                    [
                        'user_id' => 'foo',
                        'partial_password_hash' => 'c3ab8ff13720e8ad',
                        'exception_message_key' => 'key',
                        'exception_message_data' => ['data'],
                    ],
                    $event->data,
                );

                return true;
            }));

        $this->loginAuditLogger->onAuthenticationFailure($event);
    }
}
