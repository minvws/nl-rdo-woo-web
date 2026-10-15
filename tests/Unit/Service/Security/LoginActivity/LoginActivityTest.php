<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\LoginActivity;

use Shared\Service\Security\LoginActivity\LoginActivity;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

final class LoginActivityTest extends UnitTestCase
{
    public function testDefaultId(): void
    {
        $loginActivity = new LoginActivity();

        self::assertInstanceOf(WooUuid::class, $loginActivity->getId());
    }

    public function testGivenId(): void
    {
        $id = Uuid::fromString($this->getFaker()->uuid());
        $loginActivity = new LoginActivity($id);

        self::assertSame($id, $loginActivity->getId());
    }
}
