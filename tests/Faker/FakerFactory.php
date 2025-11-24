<?php

declare(strict_types=1);

namespace Shared\Tests\Faker;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;

class FakerFactory
{
    /** @var list<class-string<Base>> */
    protected static array $providers = [
        GroundsFakerProvider::class,
    ];

    public static function addProviders(Generator $faker): Generator
    {
        foreach (self::$providers as $provider) {
            $faker->addProvider(new $provider($faker));
        }

        return $faker;
    }

    public static function create(string $locale = 'nl_NL'): Generator
    {
        return self::addProviders(Factory::create($locale));
    }
}
