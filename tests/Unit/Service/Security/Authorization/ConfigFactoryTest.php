<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\Authorization;

use App\Service\Security\Authorization\ConfigFactory;
use App\Service\Security\Authorization\Entry;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ConfigFactoryTest extends MockeryTestCase
{
    public function testEmptyConfigFactory()
    {
        $factory = new ConfigFactory([]);
        $entries = $factory->create();

        $this->assertEmpty($entries);
    }

    public function testCorrectEntry()
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

        $this->assertCount(2, $entries);
        $this->assertInstanceOf(Entry::class, $entries[0]);
        $this->assertInstanceOf(Entry::class, $entries[1]);

        $this->assertEquals('prefix1', $entries[0]->getPrefix());
        $this->assertEquals('prefix2', $entries[1]->getPrefix());
    }
}
