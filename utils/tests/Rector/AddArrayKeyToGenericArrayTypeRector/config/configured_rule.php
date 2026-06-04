<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\AddArrayKeyToGenericArrayTypeRector;

return RectorConfig::configure()
    ->withRules([
        AddArrayKeyToGenericArrayTypeRector::class,
    ]);
