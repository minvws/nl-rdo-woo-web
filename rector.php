<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/apps',
        __DIR__ . '/tests',
    ])
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets()
    ->withPhpSets()
    ->withSkip([
        RemoveReflectionSetAccessibleCallsRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        ClosureToArrowFunctionRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        StringClassNameToClassConstantRector::class => [
            __DIR__ . '/tests',
            __DIR__ . '/src/class_aliases.php',
        ],
    ]);
