<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\FileReader;

use App\Service\FileReader\ColumnMapping;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ColumnMappingTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $name = 'test-col';
        $aliases = ['test', 'test-col'];

        $columnMapping = new ColumnMapping(
            $name,
            true,
            $aliases,
        );

        self::assertEquals($name, $columnMapping->getName());
        self::assertTrue($columnMapping->isRequired());
        self::assertEquals($aliases, $columnMapping->getColumnNames());
    }
}
