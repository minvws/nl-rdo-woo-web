<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi\UsageDetector;

use App\Api\OpenApi\UsageDetector\UsedComponents;
use App\Tests\Unit\UnitTestCase;

final class UsedComponentsTest extends UnitTestCase
{
    public function testNew(): void
    {
        $used = UsedComponents::new();

        $this->assertSame([
            'schemas' => [],
            'responses' => [],
            'parameters' => [],
            'examples' => [],
            'requestBodies' => [],
            'headers' => [],
            'securitySchemes' => [],
            'links' => [],
            'callbacks' => [],
            'pathItems' => [],
            'extensionProperties' => [],
        ], iterator_to_array($used));
    }

    public function testMark(): void
    {
        $used = UsedComponents::new();

        $used->mark('schemas', 'FoobarSchema');
        $used->mark('schemas', 'TestSchema');
        $used->mark('schemas', 'TestSchema'); // intentionally mark the same schema again

        $used->mark('responses', 'FoobarSchema');

        $used->mark('parameters', 'TestParameter');
        $used['parameters']['Nested'] = true; // this is only possible because offsetGet returns by reference

        $used['examples'] = [
            'FoobarExample' => true,
            'TestExample' => true,
        ];

        $this->assertTrue(isset($used['schemas']));
        $this->assertTrue(isset($used['responses']));
        $this->assertTrue(isset($used['parameters']));

        $this->assertSame([
            'FoobarSchema' => true,
            'TestSchema' => true,
        ], $used['schemas']);
        $this->assertSame([
            'FoobarSchema' => true,
        ], $used['responses']);
        $this->assertSame([
            'TestParameter' => true,
            'Nested' => true,
        ], $used['parameters']);
        $this->assertSame([
            'FoobarExample' => true,
            'TestExample' => true,
        ], $used['examples']);

        $this->assertSame([], $used['extensionProperties']);

        $this->assertMatchesSnapshot(iterator_to_array($used));
    }

    public function testHasItemsWhenEmpty(): void
    {
        $used = UsedComponents::new();

        $this->assertFalse($used->hasItems());
    }

    public function testHasItemsWhenNotEmpty(): void
    {
        $used = UsedComponents::new();

        $used->mark('callbacks', 'TestSchema');

        $this->assertTrue($used->hasItems());
    }

    public function testMarkingUnknownSectionThrowsException(): void
    {
        $used = UsedComponents::new();

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore argument.type
        $used->mark('does not exist', 'FoobarSchema');
    }

    public function testGettingUnknownSectionThrowsException(): void
    {
        $used = UsedComponents::new();

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore expr.resultUnused, offsetAccess.notFound
        $used['does not exist'];
    }

    public function testSettingUnknownSectionThrowsException(): void
    {
        $used = UsedComponents::new();

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore offsetAssign.dimType
        $used['does not exist'] = ['FoobarSchema' => true];
    }

    public function testSettingNonArrayValueThrowsException(): void
    {
        $used = UsedComponents::new();

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore offsetAssign.valueType
        $used['headers'] = 'string';
    }

    public function testUnsettingUnknownSectionThrowsException(): void
    {
        $used = UsedComponents::new();

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore unset.offset
        unset($used['does not exist']);
    }
}
