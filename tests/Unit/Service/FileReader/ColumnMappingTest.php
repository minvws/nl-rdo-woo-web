<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\FileReader;

use Shared\Service\FileReader\ColumnMapping;
use Shared\Tests\Unit\UnitTestCase;

class ColumnMappingTest extends UnitTestCase
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
