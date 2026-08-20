<?php

declare(strict_types=1);

use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Symfony\Component\DependencyInjection\Loader\Configurator\App;

/*
 * Workflow configuration using the Symfony 7.4+ array-based approach.
 *
 * Each workflow class provides its configuration via the getConfiguration() method,
 * which returns a complete array in the format expected by Symfony's workflow extension.
 */

return App::config([
    'framework' => [
        'workflows' => DossierWorkflow::getConfigs(),
    ]]);
