<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api;

use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;

use function str_repeat;

final class ExternalIdFactoryTest extends UnitTestCase
{
    public function testCreateWithValidExternalId(): void
    {
        $externalId = ExternalIdFactory::create('valid-id');

        self::assertInstanceOf(ExternalId::class, $externalId);
        self::assertEquals('valid-id', $externalId->toString());
    }

    public function testCreateWithTooLongExternalId(): void
    {
        $this->expectException(ValidationException::class);

        ExternalIdFactory::create(str_repeat('x', 129));
    }

    public function testCreateWithEmptyExternalId(): void
    {
        $this->expectException(ValidationException::class);

        ExternalIdFactory::create('');
    }
}
