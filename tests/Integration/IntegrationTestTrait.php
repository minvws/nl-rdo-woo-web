<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Organisation\Organisation;
use App\Service\Security\OrganisationSwitcher;
use App\Tests\CarbonHelpers;
use App\Tests\Faker\FakerFactory;
use Faker\Generator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

trait IntegrationTestTrait
{
    use MockeryPHPUnitIntegration;
    use MatchesSnapshots;
    use ResetDatabase;
    use Factories;
    use CarbonHelpers;

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

    public static function setActiveOrganisation(Organisation $organisation): void
    {
        $organisationSwitcher = \Mockery::mock(OrganisationSwitcher::class);
        $organisationSwitcher->expects('getActiveOrganisation')
            ->andReturn($organisation);

        self::getContainer()->set(OrganisationSwitcher::class, $organisationSwitcher);
    }
}
