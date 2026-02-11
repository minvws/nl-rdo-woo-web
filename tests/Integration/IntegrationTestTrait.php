<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use Faker\Generator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Override;
use Shared\ApplicationId;
use Shared\Domain\Organisation\Organisation;
use Shared\Kernel;
use Shared\Service\Security\OrganisationSwitcher;
use Shared\Tests\CarbonHelpers;
use Shared\Tests\Faker\FakerFactory;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
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

    abstract protected static function getAppId(): ApplicationId;

    /**
     * @param array{environment?:string,debug?:bool} $options
     */
    #[Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? (bool) ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true);

        Assert::string($env);

        return new Kernel($env, $debug, self::getAppId());
    }

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
        $organisationSwitcher = Mockery::mock(OrganisationSwitcher::class);
        $organisationSwitcher->expects('getActiveOrganisation')
            ->andReturn($organisation);

        self::getContainer()->set(OrganisationSwitcher::class, $organisationSwitcher);
    }
}
