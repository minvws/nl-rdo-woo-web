<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Expression\ForClasses\NotHaveTrait;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Shared\Tests\Integration\IntegrationTestTrait;
use Shared\Tests\Integration\Service\Search\Query\Definition\QueryDefinitionTestTrait;
use Shared\Tests\Unit\UnitTestCase;
use Spatie\Snapshots\MatchesSnapshots;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/apps',
    );

    $rules = [];

    $sharedKernelNamespaces = [
        'Admin' => 'Admin\\',
        'Public' => 'Public\\',
        'PublicationApi' => 'PublicationApi\\',
        'Worker' => 'Worker\\',
    ];

    $rules[] = Rule::namespace('Shared\\')
        ->should(new NotDependsOnTheseNamespaces([
            ...array_values($sharedKernelNamespaces),
            'WooMinVWS\\',
        ]))
        ->because('classes in Shared namespace should not depend on classes from Shared Kernel namespaces');

    foreach ($sharedKernelNamespaces as $currentLabel => $currentNamespace) {
        $selfFiltered = array_filter($sharedKernelNamespaces, static fn (string $n): bool => $currentNamespace !== $n);

        $rules[] = Rule::namespace($currentNamespace)
            ->should(new NotDependsOnTheseNamespaces($selfFiltered))
            ->because(sprintf('classes in %s namespace should not depend on classes from other Shared Kernel namespaces', $currentLabel));
    }

    $testNamespaces = [
        'Shared\\Tests',
        'Admin\\Tests',
        'Public\\Tests',
        'PublicationApi\\Tests',
        'Worker\\Tests',
        'Utils\\Tests',
        'WooMinVWS\\Tests',
    ];

    $rules[] = Rule::namespace(...$testNamespaces)
        ->should(new NotExtend(MockeryTestCase::class))
        ->because('test classes should not extend MockeryTestCase, but use UnitTestCase or IntegrationTestCase instead');

    $rules[] = Rule::allClasses()
        ->except(IntegrationTestTrait::class, UnitTestCase::class)
        ->that(new ResideInOneOfTheseNamespaces(...$testNamespaces))
        ->should(new NotHaveTrait(MockeryPHPUnitIntegration::class))
        ->because('test classes should extend the base classes that already includes MockeryPHPUnitIntegration trait');

    $rules[] = Rule::allClasses()
        ->except(IntegrationTestTrait::class, UnitTestCase::class, QueryDefinitionTestTrait::class)
        ->that(new ResideInOneOfTheseNamespaces(...$testNamespaces))
        ->should(new NotHaveTrait(MatchesSnapshots::class))
        ->because('test classes should extend the base classes that already includes MatchesSnapshots trait');

    $config
        ->add($classSet, ...$rules);
};
