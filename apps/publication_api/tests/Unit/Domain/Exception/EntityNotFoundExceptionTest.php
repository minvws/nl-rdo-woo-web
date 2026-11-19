<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Exception;

use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Exception\ProvidesDiagnosticContext;
use Shared\Tests\Unit\UnitTestCase;

final class EntityNotFoundExceptionTest extends UnitTestCase
{
    public function testForCreatesExceptionWithGenericMessage(): void
    {
        $exception = EntityNotFoundException::for('Dossier', 'abc-123');

        self::assertInstanceOf(ProvidesDiagnosticContext::class, $exception);
        self::assertSame('Entity not found', $exception->getMessage());
        self::assertSame('Dossier', $exception->entityName);
        self::assertSame('abc-123', $exception->id);
    }

    public function testGetDiagnosticContextWithStringId(): void
    {
        $exception = EntityNotFoundException::for('Dossier', 'abc-123');

        self::assertSame(
            ['entityName' => 'Dossier', 'id' => 'abc-123'],
            $exception->getDiagnosticContext(),
        );
    }
}
