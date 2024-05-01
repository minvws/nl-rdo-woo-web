<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Faker\FakerFactory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webmozart\Assert\Assert;

trait IntegrationTestTrait
{
    use MockeryPHPUnitIntegration;

    protected Generator $faker;

    public static function createFaker(): Generator
    {
        return FakerFactory::create();
    }

    public function getFaker(): Generator
    {
        if (! isset($this->faker)) {
            /** @var ContainerInterface $container */
            $container = $this->getContainer();

            $faker = $container->get(Generator::class);

            Assert::isInstanceOf($faker, Generator::class);

            $this->faker = $faker;
        }

        return $this->faker;
    }
}
