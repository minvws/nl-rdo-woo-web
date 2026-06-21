<?php

declare(strict_types=1);

use Shared\ApplicationId;
use Shared\Kernel;
use Shared\TenantResolver;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static function (array $context) {
    Assert::keyExists($context, 'APP_ENV', 'APP_ENV is required in context to create Kernel');
    $appEnv = $context['APP_ENV'];
    Assert::string($appEnv);

    Assert::keyExists($context, 'APP_ID', 'APP_ID is required in context to create Kernel');
    $appId = $context['APP_ID'];
    Assert::string($appId);

    $applicationId = ApplicationId::fromString($appId);
    $tenantId = TenantResolver::fromContext($context);

    Assert::keyExists($context, 'APP_DEBUG', 'APP_DEBUG is required in context to create Kernel');
    $appDebug = $context['APP_DEBUG'];
    Assert::nullOrScalar($appDebug);
    $appDebug = (bool) $appDebug;

    return new Kernel($appEnv, $appDebug, $applicationId, $tenantId);
};
