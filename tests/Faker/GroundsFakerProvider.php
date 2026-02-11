<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Generator;
use Faker\Provider\Base;
use Shared\Domain\Publication\Citation;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_unique;
use function array_values;

final class GroundsFakerProvider extends Base
{
    /**
     * @var list<string>
     */
    private static array $grounds;

    public function __construct(Generator $generator)
    {
        parent::__construct($generator);

        $this->updateGrounds();
    }

    public function ground(): string
    {
        return static::randomElement(self::$grounds);
    }

    /**
     * @return list<string>
     */
    public function grounds(int $count = 1): array
    {
        return array_values(static::randomElements(self::$grounds, $count));
    }

    /**
     * @return list<string>
     */
    public function groundsBetween(int $min = 0, int $max = 3): array
    {
        Assert::natural($min, 'The minimum number of grounds must be non-negative number');
        Assert::positiveInteger($max, 'The maximum number of grounds must be a positive integer');

        return array_values(static::randomElements(self::$grounds, static::numberBetween($min, $max)));
    }

    private function updateGrounds(): void
    {
        if (isset(self::$grounds)) {
            return;
        }

        self::$grounds = array_values(array_unique([
            ...array_keys(Citation::$wooCitations),
            ...array_keys(Citation::$wobCitations),
        ]));
    }
}
