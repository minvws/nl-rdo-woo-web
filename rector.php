<?php

declare(strict_types=1);

use Utils\Rector\AddArrayKeyToGenericArrayTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Rector\Symfony\Symfony42\Rector\New_\StringToArrayArgumentProcessRector;
use Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector;

// All test directories within the configured paths below.
$testPaths = [
    __DIR__ . '/tests',
    __DIR__ . '/apps/*/tests/*',
    __DIR__ . '/tenants/*/tests/*',
    __DIR__ . '/utils/tests/*',
];

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/apps',
        __DIR__ . '/tenants',
        __DIR__ . '/tests',
        __DIR__ . '/utils',
    ])
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets()
    ->withPhpSets()
    ->withrules([
        AddArrayKeyToGenericArrayTypeRector::class,
    ])
    ->withSkip([
        RemoveReflectionSetAccessibleCallsRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ClosureToArrowFunctionRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        StringClassNameToClassConstantRector::class => $testPaths,
        // Rewrites Mockery `->expects('method')` calls on mocked Process objects into arrays
        StringToArrayArgumentProcessRector::class => $testPaths,
        CommandConfigureToAttributeRector::class => $testPaths,
        __DIR__ . '/utils/tests/PHPStan/**/data/*'
    ]);
