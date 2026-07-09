<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Provider\Base;
use UnitEnum;

use function is_array;

final class BaseFakerProvider extends Base
{
    /**
     * @template T of UnitEnum
     *
     * @param class-string<T>|list<T> $enum
     *
     * @return T
     */
    public function randomEnum(string|array $enum): UnitEnum
    {
        $cases = is_array($enum) ? $enum : $enum::cases();

        return $this->pickRandom($cases);
    }

    /**
     * @template T
     *
     * @param list<T> $array
     *
     * @return T
     */
    private function pickRandom(array $array)
    {
        return self::randomElement($array);
    }
}
