<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\OpenApi\UsageDetector;

use App\Api\OpenApi\UsageDetector\UsageQueue;
use App\Api\OpenApi\UsageDetector\UsedComponents;
use App\Tests\Unit\UnitTestCase;

final class UsageQueueTest extends UnitTestCase
{
    public function testNew(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->assertSame([], iterator_to_array($queue));
    }

    public function testNewWithUsedComponentsHavingValues(): void
    {
        $used = UsedComponents::new();

        $used->mark('schemas', 'FoobarSchema');
        $used->mark('schemas', 'TestSchema');
        $used->mark('responses', 'FoobarSchema');
        $used->mark('parameters', 'TestParameter');
        $used->mark('examples', 'FoobarExample');
        $used->mark('examples', 'TestExample');

        $queue = UsageQueue::new($used);

        $this->assertMatchesSnapshot(iterator_to_array($queue));
    }

    public function testHasItemsWhenEmpty(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->assertFalse($queue->hasItems());
    }

    public function testHasItemsWhenNotEmpty(): void
    {
        $used = UsedComponents::new();
        $used->mark('callbacks', 'TestSchema');

        $queue = UsageQueue::new($used);

        $this->assertTrue($queue->hasItems());
    }

    public function testAdd(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $queue->add('schemas', 'FoobarSchema');
        $queue->add('schemas', 'FoobarSchema'); // intentionally add the same schema again

        $this->assertTrue(isset($queue['schemas']));
        $this->assertSame(['FoobarSchema'], $queue['schemas']);
    }

    public function testAddingUnknownSectionThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore argument.type
        $queue->add('does not exist', 'FoobarSchema');
    }

    public function testGettingUnknownSectionThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore expr.resultUnused, offsetAccess.notFound
        $queue['does not exist'];
    }

    public function testGettingKnownSectionWithoutValuesThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore expr.resultUnused
        $queue['schemas'];
    }

    public function testSettingUnknownSectionThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore offsetAssign.dimType
        $queue['does not exist'] = ['FoobarSchema'];
    }

    public function testSettingKnownSection(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $queue['schemas'] = ['FoobarSchema'];

        $this->assertTrue(isset($queue['schemas']));
        $this->assertSame(['FoobarSchema'], $queue['schemas']);
    }

    public function testSettingNonArrayValueThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore offsetAssign.valueType
        $queue['headers'] = 'string';
    }

    public function testUnset(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());
        $queue->add('schemas', 'FoobarSchema');
        $queue->add('schemas', 'TestSchema');

        $this->assertTrue(isset($queue['schemas']));

        unset($queue['schemas']);

        $this->assertFalse(isset($queue['schemas']));
    }

    public function testUnsettingUnknownSectionThrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore unset.offset
        unset($queue['does not exist']);
    }

    public function testPop(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $queue->add('schemas', 'FoobarSchema');
        $queue->add('schemas', 'TestSchema');
        $queue->add('responses', 'FoobarResponse');
        $queue->add('headers', 'MyHeader');

        $valueOne = $queue->pop('schemas');
        $valueTwo = $queue->pop('schemas');
        $valueThree = $queue->pop('responses');
        $valueFour = $queue->pop('responses'); // should return null since no more responses

        $this->assertSame('TestSchema', $valueOne);
        $this->assertSame('FoobarSchema', $valueTwo);
        $this->assertSame('FoobarResponse', $valueThree);
        $this->assertNull($valueFour);
        $this->assertSame([
            'headers' => ['MyHeader'],
        ], iterator_to_array($queue));
    }

    public function testPopOnUnknownSectionthrowsException(): void
    {
        $queue = UsageQueue::new(UsedComponents::new());

        $this->expectException(\InvalidArgumentException::class);

        $queue->pop('does not exist');
    }
}
