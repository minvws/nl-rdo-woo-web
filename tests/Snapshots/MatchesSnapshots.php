<?php

declare(strict_types=1);

namespace Shared\Tests\Snapshots;

use Spatie\Snapshots\Driver;
use Spatie\Snapshots\MatchesSnapshots as SpatieMatchesSnapshots;

use function is_float;
use function is_int;
use function is_string;

trait MatchesSnapshots
{
    use SpatieMatchesSnapshots {
        assertMatchesSnapshot as private assertMatchesSpatieSnapshot;
    }

    public function assertMatchesSnapshot(mixed $actual, ?Driver $driver = null, ?string $id = null): void
    {
        if ($driver === null && ! is_string($actual) && ! is_int($actual) && ! is_float($actual)) {
            $driver = new ObjectDriver();
        }

        $this->assertMatchesSpatieSnapshot($actual, $driver, $id);
    }

    public function assertMatchesObjectSnapshot(mixed $actual, ?string $id = null): void
    {
        $this->assertMatchesSnapshot($actual, new ObjectDriver(), $id);
    }
}
