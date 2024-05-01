<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\Faker\FakerFactory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class UnitTestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;
    use MatchesSnapshots;

    protected Generator $faker;

    public static function createFaker(): Generator
    {
        return FakerFactory::create();
    }

    public function getFaker(): Generator
    {
        if (! isset($this->faker)) {
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
            'Make sure to inspect the created snapshot afterwards to ensure its correctness!'
        );
    }
}
