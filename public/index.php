<?php

use Shared\ApplicationId;
use Shared\Kernel;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

require_once dirname(__DIR__) . '/src/class_aliases.php';

return function (array $context) {
    Assert::string($context['APP_ENV']);
    Assert::string($context['APP_ID']);

    $applicationId = ApplicationId::fromString($context['APP_ID']);

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG'], $applicationId);
};
