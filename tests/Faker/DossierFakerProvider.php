<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Generator;
use Faker\Provider\Internet;
use Shared\Domain\Publication\Citation;
use Webmozart\Assert\Assert;

use function array_unique;
use function array_values;

final class DossierFakerProvider extends Internet
{
    /**
     * @var list<string>
     */
    private static array $grounds = [];

    public function __construct(Generator $generator)
    {
        parent::__construct($generator);

        self::$grounds = array_values(array_unique(Citation::ALL_GROUND_KEYS));
    }

    public function dossierNumber(): string
    {
        return $this->slug(2);
    }

    public function ground(): string
    {
        $ground = static::randomElement(self::$grounds);
        Assert::string($ground);

        return $ground;
    }

    /**
     * @return list<string>
     */
    public function grounds(int $count = 1): array
    {
        $grounds = array_values(static::randomElements(self::$grounds, $count));
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
