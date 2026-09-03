<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use Shared\Domain\Exception\ProvidesDiagnosticContext;
use Shared\Domain\Logging\ProvidesDiagnosticContextProcessor;
use Shared\Tests\Unit\UnitTestCase;
use Throwable;

final class ProvidesDiagnosticContextProcessorTest extends UnitTestCase
{
    private ProvidesDiagnosticContextProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new ProvidesDiagnosticContextProcessor();
    }

    public function testRecordWithoutExceptionIsReturnedUnchanged(): void
    {
        $record = $this->createRecord(extra: ['foo' => 'bar']);

        $result = ($this->processor)($record);

        self::assertSame($record, $result);
        self::assertSame(['foo' => 'bar'], $result->extra);
    }

    public function testRecordWithNonThrowableExceptionContextIsReturnedUnchanged(): void
    {
        $record = $this->createRecord(context: ['exception' => 'not a throwable']);

        $result = ($this->processor)($record);

        self::assertSame($record, $result);
        self::assertSame([], $result->extra);
    }

    public function testRecordWithThrowableWithoutDiagnosticContextIsReturnedUnchanged(): void
    {
        $record = $this->createRecord(context: ['exception' => new RuntimeException('boom')]);

        $result = ($this->processor)($record);

        self::assertSame($record, $result);
        self::assertSame([], $result->extra);
    }

    public function testDiagnosticContextIsAddedToExtra(): void
    {
        $exception = $this->exceptionWithContext(['entityName' => 'Dossier', 'id' => 'abc-123']);
        $record = $this->createRecord(context: ['exception' => $exception]);

        $result = ($this->processor)($record);

        self::assertSame(['entityName' => 'Dossier', 'id' => 'abc-123'], $result->extra);
    }

    public function testExistingExtraIsPreserved(): void
    {
        $exception = $this->exceptionWithContext(['entityName' => 'Dossier']);
        $record = $this->createRecord(
            context: ['exception' => $exception],
            extra: ['user_id' => 42],
        );

        $result = ($this->processor)($record);

        self::assertSame(['user_id' => 42, 'entityName' => 'Dossier'], $result->extra);
    }

    public function testDiagnosticContextOverwritesExistingExtraOnKeyCollision(): void
    {
        $exception = $this->exceptionWithContext(['entityName' => 'Dossier']);
        $record = $this->createRecord(
            context: ['exception' => $exception],
            extra: ['entityName' => 'stale'],
        );

        $result = ($this->processor)($record);

        self::assertSame(['entityName' => 'Dossier'], $result->extra);
    }

    public function testAllOtherRecordFieldsAreLeftIntact(): void
    {
        $exception = $this->exceptionWithContext(['entityName' => 'Dossier']);
        $record = $this->createRecord(context: ['exception' => $exception]);

        $result = ($this->processor)($record);

        self::assertNotSame($record, $result);
        self::assertSame($record->datetime, $result->datetime);
        self::assertSame($record->channel, $result->channel);
        self::assertSame($record->level, $result->level);
        self::assertSame($record->message, $result->message);
        self::assertSame($record->context, $result->context);
    }

    public function testDiagnosticContextOfWrappedExceptionIsAdded(): void
    {
        $exception = new RuntimeException(
            'wrapper',
            0,
            $this->exceptionWithContext(['entityName' => 'Dossier']),
        );
        $record = $this->createRecord(context: ['exception' => $exception]);

        $result = ($this->processor)($record);

        self::assertSame(['entityName' => 'Dossier'], $result->extra);
    }

    public function testDiagnosticContextIsCollectedFromTheWholePreviousChain(): void
    {
        $exception = $this->exceptionWithContext(
            ['outer' => 'a'],
            new RuntimeException(
                'middle',
                0,
                $this->exceptionWithContext(['inner' => 'b']),
            ),
        );
        $record = $this->createRecord(context: ['exception' => $exception]);

        $result = ($this->processor)($record);

        self::assertSame(['outer' => 'a', 'inner' => 'b'], $result->extra);
    }

    public function testOutermostExceptionWinsOnKeyCollision(): void
    {
        $exception = $this->exceptionWithContext(
            ['entityName' => 'outermost'],
            $this->exceptionWithContext(['entityName' => 'innermost']),
        );
        $record = $this->createRecord(context: ['exception' => $exception]);

        $result = ($this->processor)($record);

        self::assertSame(['entityName' => 'outermost'], $result->extra);
    }

    public function testEmptyDiagnosticContextLeavesTheRecordUntouched(): void
    {
        $record = $this->createRecord(context: ['exception' => $this->exceptionWithContext([])]);

        $result = ($this->processor)($record);

        self::assertSame($record, $result);
        self::assertSame([], $result->extra);
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function createRecord(array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable('2026-08-22 12:00:00'),
            channel: 'app',
            level: Level::Error,
            message: $this->getFaker()->sentence(),
            context: $context,
            extra: $extra,
        );
    }

    /**
     * @param array<string, scalar|null> $diagnosticContext
     */
    private function exceptionWithContext(
        array $diagnosticContext,
        ?Throwable $previous = null,
    ): ProvidesDiagnosticContext {
        return new class($diagnosticContext, $previous) extends RuntimeException implements ProvidesDiagnosticContext {
            /**
             * @param array<string, scalar|null> $diagnosticContext
             */
            public function __construct(
                private readonly array $diagnosticContext,
                ?Throwable $previous,
            ) {
                parent::__construct('diagnostic', 0, $previous);
            }

            public function getDiagnosticContext(): array
            {
                return $this->diagnosticContext;
            }
        };
    }
}
