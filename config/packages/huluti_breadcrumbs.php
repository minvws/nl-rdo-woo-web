<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'huluti_breadcrumbs' => [
        'separator' => '/',
        'separatorClass' => 'separator',
        'listId' => 'wo-breadcrumbs',
        'listClass' => 'breadcrumb',
        'itemClass' => '',
        'linkRel' => '',
        'locale' => null, // defaults to null, so the default locale is used
        'translation_domain' => null, // defaults to null, so the default domain is used
        'viewTemplate' => 'public/layout/breadcrumbs.html.twig',
    ],
]);
