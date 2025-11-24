<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\Authorization;

use Shared\Service\Security\Authorization\ConfigFactory;
use Shared\Service\Security\Authorization\Entry;
use Shared\Tests\Unit\UnitTestCase;

class ConfigFactoryTest extends UnitTestCase
{
    public function testEmptyConfigFactory(): void
    {
        $factory = new ConfigFactory([]);
        $entries = $factory->create();

        self::assertEmpty($entries);
    }

    public function testCorrectEntry(): void
    {
        $config = [
            'entries' => [
                [
                    'prefix' => 'prefix1',
                    'roles' => ['ROLE_TEST'],
                    'permissions' => ['create' => true],
                    'filters' => ['filter1' => true],
                ],
                [
                    'prefix' => 'prefix2',
                    'roles' => ['ROLE_TEST'],
                    'permissions' => ['create' => true],
                    'filters' => ['filter1' => true],
                ],
            ],
        ];

        $factory = new ConfigFactory($config);
        $entries = $factory->create();

        self::assertCount(2, $entries);
        self::assertInstanceOf(Entry::class, $entries[0]);
        self::assertInstanceOf(Entry::class, $entries[1]);

        self::assertEquals('prefix1', $entries[0]->getPrefix());
        self::assertEquals('prefix2', $entries[1]->getPrefix());
    }
}
