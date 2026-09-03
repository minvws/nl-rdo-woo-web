<?php

declare(strict_types=1);

namespace Shared\Tests\Unit;

use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Shared\Tests\CarbonHelpers;
use Shared\Tests\Faker\FakerFactory;
use Spatie\Snapshots\MatchesSnapshots;

abstract class UnitTestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;
    use MatchesSnapshots;
    use CarbonHelpers;

    protected ?Generator $faker = null;

    public static function createFaker(): Generator
    {
        return FakerFactory::create();
    }

    public function getFaker(): Generator
    {
        if ($this->faker === null) {
            $this->faker = $this->createFaker();
        }

        return $this->faker;
    }

    protected function assertSnapshotShouldBeCreated(string $snapshotFileName): void
    {
        if ($this->shouldCreateSnapshots()) {
            return;
        }

        static::fail(
            "Snapshot \"$snapshotFileName\" does not exist.\n" .
            "You can automatically create it by running \"composer update-test-snapshots\".\n" .
            'Make sure to inspect the created snapshot afterwards to ensure its correctness!',
        );
    }
}
