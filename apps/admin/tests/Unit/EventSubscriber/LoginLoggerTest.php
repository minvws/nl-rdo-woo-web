<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\EventSubscriber;

use Admin\Domain\Authentication\UserRouteHelper;
use Admin\EventSubscriber\LoginLogger;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginLoggerTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private RouterInterface&MockInterface $router;
    private UserRouteHelper&MockInterface $userRouteHelper;
    private LoginLogger $subscriber;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->router = Mockery::mock(RouterInterface::class);
        $this->userRouteHelper = Mockery::mock(UserRouteHelper::class);

        $this->subscriber = new LoginLogger(
            $this->logger,
            $this->router,
            $this->userRouteHelper,
        );
    }

    public function testOnLogout(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('getUserIdentifier')->andReturn($userId = 'foo123');

        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new LogoutEvent(
            Mockery::mock(Request::class),
            $token,
        );

        $this->logger->expects('log')->with('info', 'Logout success', ['user_id' => $userId]);

        $this->subscriber->onLogout($event);
    }

    public function testOnAuthenticationSucces(): void
    {
        $user = Mockery::mock(User::class);

        $event = Mockery::mock(LoginSuccessEvent::class);
        $event->expects('getUser')->andReturn($user);

        $this->userRouteHelper->expects('getDefaultIndexRouteName')->andReturn('foo');

        $this->router->expects('generate')->with('foo')->andReturn($url = 'foo/bar');

        $event->expects('setResponse')->with(Mockery::on(
            static function (RedirectResponse $response) use ($url): bool {
                return $response->getTargetUrl() === $url;
            }
        ));

        $this->subscriber->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationFailure(): void
    {
        $exception = new AuthenticationException();

        $event = Mockery::mock(LoginFailureEvent::class);
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
