<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Updater;

use Closure;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Search\Index\Updater\RetryIndexUpdaterTrait;
use Shared\Tests\Unit\UnitTestCase;

use function array_column;
use function array_unique;

final class RetryIndexUpdaterTraitTest extends UnitTestCase
{
    private const int MAX_RETRIES = 10;
    private const string RETRY_MESSAGE = '[Elasticsearch] Update dossier version mismatch. Retrying...';

    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);

        parent::setUp();
    }

    public function testCallableIsInvokedOnceAndNothingIsLoggedOnSuccess(): void
    {
        $calls = 0;

        $this->retryWith(static function () use (&$calls): void {
            $calls++;
        });

        self::assertSame(1, $calls);
    }

    public function testVersionConflictIsRetriedUntilTheCallableSucceeds(): void
    {
        $notices = [];
        $this->logger->expects('notice')
            ->twice()
            ->with(self::RETRY_MESSAGE, Mockery::on($this->collectRetryContexts($notices)));

        $calls = 0;
        $waits = [];
        $this->retryWith(static function () use (&$calls): void {
            $calls++;

            if ($calls <= 2) {
                throw new ClientResponseException('version conflict', 409);
            }
        }, $waits);

        self::assertSame(3, $calls);
        self::assertSame([0, 1], array_column($notices, 'attemptCount'));
        self::assertSame([100000, 140000], array_column($notices, 'waitUs'));
        self::assertSame([100000, 140000], $waits);
    }

    public function testEveryAttemptOfOneRetrySequenceSharesTheSameAttemptId(): void
    {
        $notices = [];
        $this->logger->expects('notice')
            ->twice()
            ->with(self::RETRY_MESSAGE, Mockery::on($this->collectRetryContexts($notices)));

        $calls = 0;
        $this->retryWith(static function () use (&$calls): void {
            $calls++;

            if ($calls <= 2) {
                throw new ClientResponseException('version conflict', 409);
            }
        });

        $attemptIds = array_column($notices, 'attemptId');

        self::assertCount(2, $attemptIds);
        self::assertNotSame('', $attemptIds[0]);
        self::assertCount(1, array_unique($attemptIds));
    }

    public function testSeparateRetrySequencesGetDifferentAttemptIds(): void
    {
        $notices = [];
        $this->logger->expects('notice')
            ->twice()
            ->with(self::RETRY_MESSAGE, Mockery::on($this->collectRetryContexts($notices)));

        $calls = 0;
        $failOnce = static function () use (&$calls): void {
            $calls++;

            if ($calls % 2 === 1) {
                throw new ClientResponseException('version conflict', 409);
            }
        };

        $this->retryWith($failOnce);
        $this->retryWith($failOnce);

        self::assertCount(2, array_unique(array_column($notices, 'attemptId')));
    }

    public function testNonConflictErrorIsLoggedAndRethrownWithoutRetrying(): void
    {
        $errors = [];
        $this->logger->expects('error')
            ->once()
            ->with('[Elasticsearch] An error occurred: {message}', Mockery::on($this->collectErrorContexts($errors)));

        $calls = 0;
        $waits = [];
        $exception = null;

        try {
            $this->retryWith(static function () use (&$calls): void {
                $calls++;

                throw new ClientResponseException('index missing', 404);
            }, $waits);
        } catch (ClientResponseException $caught) {
            $exception = $caught;
        }

        self::assertNotNull($exception);
        self::assertSame(404, $exception->getCode());
        self::assertSame(1, $calls);
        self::assertSame([], $waits);
        self::assertCount(1, $errors);
        self::assertSame('index missing', $errors[0]['message']);
        self::assertSame(404, $errors[0]['code']);
        self::assertNotSame('', $errors[0]['attemptId']);
    }

    public function testExceptionIsRethrownAfterMaxRetries(): void
    {
        $notices = [];
        $this->logger->expects('notice')
            ->times(self::MAX_RETRIES)
            ->with(self::RETRY_MESSAGE, Mockery::on($this->collectRetryContexts($notices)));

        $errors = [];
        $this->logger->expects('error')
            ->once()
            ->with('[Elasticsearch] Too many retries', Mockery::on($this->collectErrorContexts($errors)));

        $calls = 0;
        $waits = [];
        $exception = null;

        try {
            $this->retryWith(static function () use (&$calls): void {
                $calls++;

                throw new ClientResponseException('version conflict', 409);
            }, $waits);
        } catch (ClientResponseException $caught) {
            $exception = $caught;
        }

        self::assertNotNull($exception);
        self::assertSame(409, $exception->getCode());
        self::assertSame(self::MAX_RETRIES + 1, $calls);
        self::assertSame([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], array_column($notices, 'attemptCount'));
        self::assertSame(
            [100000, 140000, 196000, 274400, 384160, 537824, 752954, 1054136, 1475790, 2066105],
            array_column($notices, 'waitUs'),
        );
        self::assertSame(
            [100000, 140000, 196000, 274400, 384160, 537824, 752954, 1054136, 1475790, 2066105],
            $waits,
        );
        self::assertCount(1, array_unique(array_column($notices, 'attemptId')));
        self::assertCount(1, $errors);
        self::assertSame('version conflict', $errors[0]['message']);
        self::assertSame(409, $errors[0]['code']);
        self::assertSame($notices[0]['attemptId'], $errors[0]['attemptId']);
    }

    public function testOtherExceptionsAreNotCaughtAndNothingIsLogged(): void
    {
        $this->expectExceptionObject(new RuntimeException('unrelated'));

        $this->retryWith(static function (): void {
            throw new RuntimeException('unrelated');
        });
    }

    /**
     * @param list<int> $waits
     */
    private function retryWith(callable $fn, array &$waits = []): void
    {
        $recordWait = static function (int $waitUs) use (&$waits): void {
            $waits[] = $waitUs;
        };

        $subject = new readonly class($this->logger, $recordWait) {
            use RetryIndexUpdaterTrait;

            public function __construct(
                private LoggerInterface $logger,
                private Closure $recordWait,
            ) {
            }

            public function run(callable $fn): void
            {
                $this->retry($fn);
            }

            protected function wait(int $waitUs): void
            {
                ($this->recordWait)($waitUs);
            }

            protected function getLogger(): LoggerInterface
            {
                return $this->logger;
            }
        };

        $subject->run($fn);
    }

    /**
     * @param list<array{attemptId: string, attemptCount: int, waitUs: int}> $store
     *
     * @return Closure(array{attemptId: string, attemptCount: int, waitUs: int}): bool
     */
    private function collectRetryContexts(array &$store): Closure
    {
        return static function (array $context) use (&$store): bool {
            $store[] = $context;

            return true;
        };
    }

    /**
     * @param list<array{attemptId: string, message: string, code: int}> $store
     *
     * @return Closure(array{attemptId: string, message: string, code: int}): bool
     */
    private function collectErrorContexts(array &$store): Closure
    {
        return static function (array $context) use (&$store): bool {
            $store[] = $context;

            return true;
        };
    }
}
