<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LoginLogger;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\User;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginLoggerTest extends MockeryTestCase
{
    private LoggerInterface&MockInterface $logger;
    private RouterInterface&MockInterface $router;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private LoginLogger $subscriber;

    public function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->router = \Mockery::mock(RouterInterface::class);
        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);

        $this->subscriber = new LoginLogger(
            $this->logger,
            $this->router,
            $this->authorizationMatrix,
        );
    }

    public function testOnLogout(): void
    {
        $user = \Mockery::mock(User::class);
        $user->expects('getUserIdentifier')->andReturn($userId = 'foo123');

        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new LogoutEvent(
            \Mockery::mock(Request::class),
            $token,
        );

        $this->logger->expects('log')->with('info', 'Logout success', ['user_id' => $userId]);

        $this->subscriber->onLogout($event);
    }

    public function testOnAuthenticationSucces(): void
    {
        $user = \Mockery::mock(User::class);

        $event = \Mockery::mock(LoginSuccessEvent::class);
        $event->expects('getUser')->andReturn($user);

        $this->authorizationMatrix->expects('isAuthorized')->andReturnFalse();

        $this->router->expects('generate')->with('app_admin_users')->andReturn($url = 'foo/bar');

        $event->expects('setResponse')->with(\Mockery::on(
            static function (RedirectResponse $response) use ($url): bool {
                return $response->getTargetUrl() === $url;
            }
        ));

        $this->subscriber->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationFailure(): void
    {
        $exception = new AuthenticationException();

        $event = \Mockery::mock(LoginFailureEvent::class);
        $event->expects('getException')->andReturn($exception);
        $event->expects('getPassport->getBadge->getUserIdentifier')->andReturn($userIdentifier = 'foo123');

        $this->logger->expects('error')->with(
            'Login failure',
            [
                'exception_message_key' => $exception->getMessageKey(),
                'exception_message_data' => $exception->getMessageData(),
                'login_name' => $userIdentifier,
            ],
        );

        $this->subscriber->onAuthenticationFailure($event);
    }
}
