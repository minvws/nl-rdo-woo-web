<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Internet;
use Shared\Domain\Publication\Citation;
use Webmozart\Assert\Assert;

use function array_diff;
use function array_keys;
use function array_unique;
use function array_values;

final class DossierFakerProvider extends Internet
{
    public function dossierNumber(): string
    {
        return $this->slug(2);
    }

    public function ground(): string
    {
        return $this->grounds()[0];
    }

    public function wooGround(): string
    {
        $ground = static::randomElement(array_keys(Citation::$wooCitations));
        Assert::string($ground);

        return $ground;
    }

    public function wobGround(): string
    {
        $ground = static::randomElement(array_values(array_diff(Citation::WOB_GROUND_KEYS, [Citation::DUBBEL])));
        Assert::string($ground);

        return $ground;
    }

    /**
     * @return list<string>
     */
    public function grounds(int $count = 1): array
    {
        $grounds = array_values(static::randomElements(array_values(array_unique(Citation::ALL_GROUND_KEYS)), $count));
        Assert::allString($grounds);

        return $grounds;
    }

    /**
     * @return list<string>
     */
    public function groundsBetween(int $min = 0, int $max = 3): array
    {
        Assert::natural($min, 'The minimum number of grounds must be non-negative number');
        Assert::positiveInteger($max, 'The maximum number of grounds must be a positive integer');

        return $this->grounds(static::numberBetween($min, $max));
    }
}
