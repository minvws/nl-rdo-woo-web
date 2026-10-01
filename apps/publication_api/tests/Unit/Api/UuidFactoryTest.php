<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api;

use PublicationApi\Api\UuidFactory;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class UuidFactoryTest extends UnitTestCase
{
    public function testCreateReturnsUuidForValidUuid(): void
    {
        $uuid = Uuid::v6();

        $result = UuidFactory::create($uuid->toRfc4122());

        self::assertSame($uuid->toRfc4122(), $result->toRfc4122());
    }

    public function testCreateTranslatesInvalidUuidToValidationException(): void
    {
        $this->expectException(ValidationException::class);

        UuidFactory::create('not-a-uuid');
    }
}
