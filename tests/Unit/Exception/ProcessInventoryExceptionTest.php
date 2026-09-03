<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Exception;

use Mockery;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Exception\ProcessInventoryException;
use Shared\Exception\TranslatableException;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;

final class ProcessInventoryExceptionTest extends UnitTestCase
{
    public function testForInventoryCannotBeStored(): void
    {
        self::assertInstanceOf(
            TranslatableException::class,
            ProcessInventoryException::forInventoryCannotBeStored(),
        );
    }

    public function testForInventoryCannotBeLoadedFromStorage(): void
    {
        self::assertInstanceOf(
            TranslatableException::class,
            ProcessInventoryException::forInventoryCannotBeLoadedFromStorage(),
        );
    }

    public function testForMissingDocument(): void
    {
        self::assertStringContainsString(
            'foo-123',
            ProcessInventoryException::forMissingDocument('foo-123')->getMessage(),
        );
    }

    public function testForOtherException(): void
    {
        self::assertStringContainsString(
            'foo-123',
            ProcessInventoryException::forOtherException(new RuntimeException('foo-123'))->getMessage(),
        );
    }

    public function testForNoChanges(): void
    {
        self::assertInstanceOf(
            TranslatableException::class,
            ProcessInventoryException::forNoChanges(),
        );
    }

    public function testForMaxRuntimeExceeded(): void
    {
        self::assertInstanceOf(
            TranslatableException::class,
            ProcessInventoryException::forMaxRuntimeExceeded(),
        );
    }

    public function testForMissingReferredDocument(): void
    {
        self::assertStringContainsString(
            'foo-123',
            ProcessInventoryException::forMissingReferredDocument('foo-123')->getMessage(),
        );
    }

    public function testForDuplicateDocumentNumber(): void
    {
        self::assertStringContainsString(
            'foo-123',
            ProcessInventoryException::forDuplicateDocumentNumber('foo-123')->getMessage(),
        );
    }

    public function testForDocumentExistsInAnotherDossier(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentId')
            ->times(2)
            ->andReturn(DocumentId::create('foo-456'));

        self::assertStringContainsString(
            'foo-456',
            ProcessInventoryException::forDocumentExistsInAnotherDossier($document)->getMessage(),
        );
    }

    public function testForGenericRowException(): void
    {
        self::assertStringContainsString(
            'foo-123',
            ProcessInventoryException::forGenericRowException(new RuntimeException('foo-123'))->getMessage(),
        );
    }
}
