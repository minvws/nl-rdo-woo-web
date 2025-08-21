<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Logging;

use App\Service\Logging\EnrichedPsrLogger;
use App\Service\Security\User;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

final class EnrichedPsrLoggerTest extends UnitTestCase
{
    private const array IPS = ['127.0.0.1'];
    private const array MY_ROLES = ['Chief Chaos Coordinator'];

    private Logger&MockInterface $logger;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private RequestStack&MockInterface $requestStack;
    private Request&MockInterface $request;
    private User&MockInterface $user;
    private Uuid $userId;
    private TokenInterface&MockInterface $token;

    protected function setUp(): void
    {
        $this->request = \Mockery::mock(Request::class);
        $this->request->shouldReceive('getClientIps')->andReturn(self::IPS);

        $this->logger = \Mockery::mock(Logger::class);

        $this->userId = Uuid::v6();

        $this->user = \Mockery::mock(User::class);
        $this->user->shouldReceive('getId')->andReturn($this->userId);
        $this->user->shouldReceive('getRoles')->andReturn(self::MY_ROLES);

        $this->token = \Mockery::mock(TokenInterface::class);
        $this->token->shouldReceive('getUser')->andReturn($this->user);

        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $this->tokenStorage->shouldReceive('getToken')->andReturn($this->token);

        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);
    }

    #[DataProvider('logLevels')]
    public function testLogging(string $logLevel): void
    {
        $this->logger
            ->shouldReceive($logLevel)
            ->once()
            ->with(
                'my message',
                [
                    'context' => 'my context',
                    'user_info' => [
                        'ip' => self::IPS,
                        'id' => (string) $this->userId,
                        'roles' => self::MY_ROLES,
                    ],
                ]
            );

        $this->getLogger()->{$logLevel}('my message', ['context' => 'my context']);
    }

    public function testLoggingWithStringable(): void
    {
        $message = new class implements \Stringable {
            public function __toString(): string
            {
                return 'my message';
            }
        };

        $this->logger
            ->shouldReceive('alert')
            ->once()
            ->with(
                $message,
                [
                    'context' => 'my context',
                    'user_info' => [
                        'ip' => self::IPS,
                        'id' => (string) $this->userId,
                        'roles' => self::MY_ROLES,
                    ],
                ]
            );

        $this->getLogger()->alert($message, ['context' => 'my context']);
    }

    #[DataProvider('logLevels')]
    public function testLog(string $logLevel): void
    {
        $this->logger
            ->shouldReceive('log')
            ->once()
            ->with(
                $logLevel,
                'my message',
                [
                    'context' => 'my context',
                    'user_info' => [
                        'ip' => self::IPS,
                        'id' => (string) $this->userId,
                        'roles' => self::MY_ROLES,
                    ],
                ]
            );

        $this->getLogger()->log($logLevel, 'my message', ['context' => 'my context']);
    }

    /**
     * @return array<string,array{logLevel:string}>
     */
    public static function logLevels(): array
    {
        return [
            'emergency' => ['logLevel' => 'emergency'],
            'alert' => ['logLevel' => 'alert'],
            'critical' => ['logLevel' => 'critical'],
            'error' => ['logLevel' => 'error'],
            'warning' => ['logLevel' => 'warning'],
            'notice' => ['logLevel' => 'notice'],
            'info' => ['logLevel' => 'info'],
            'debug' => ['logLevel' => 'debug'],
        ];
    }

    private function getLogger(): EnrichedPsrLogger
    {
        $normalizerFormatter = \Mockery::mock(NormalizerFormatter::class);
        $normalizerFormatter->shouldReceive('setMaxNormalizeDepth')->once()->with(20);

        /** @var FormattableHandlerInterface&MockInterface $formattableHandler */
        $formattableHandler = \Mockery::mock(FormattableHandlerInterface::class);
        $formattableHandler->shouldReceive('getFormatter')->andReturn($normalizerFormatter);

        $handler = \Mockery::mock(HandlerInterface::class);
        $handler->shouldNotReceive('getFormatter');

        $this->logger
            ->shouldReceive('getHandlers')
            ->andReturn([$formattableHandler, $handler]);

        return new EnrichedPsrLogger($this->logger, $this->tokenStorage, $this->requestStack);
    }
}
