<?php

declare(strict_types=1);

use Shared\ApplicationId;
use Shared\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/.env');

$applicationId = ApplicationId::fromString($_SERVER['APP_ID']);

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'], $applicationId);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
