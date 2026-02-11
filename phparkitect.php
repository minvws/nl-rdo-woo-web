<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src', __DIR__ . '/apps');

    $rules = [];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Shared\\'))
        ->should(new NotDependsOnTheseNamespaces([
            'Admin\\',
            'Public\\',
            'PublicationApi\\',
            'Worker\\',
        ]))
        ->because('classes in Shared namespace should not depend on classes from Shared Kernel namespaces');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Admin\\'))
        ->should(new NotDependsOnTheseNamespaces([
            'Public\\',
            'PublicationApi\\',
            'Worker\\',
        ]))
        ->because('classes in Admin namespace should not depend on classes from other Shared Kernel namespaces');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Public\\'))
        ->should(new NotDependsOnTheseNamespaces([
            'Admin\\',
            'PublicationApi\\',
            'Worker\\',
        ]))
        ->because('classes in Public namespace should not depend on classes from other Shared Kernel namespaces');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('PublicationApi\\'))
        ->should(new NotDependsOnTheseNamespaces([
            'Admin\\',
            'Public\\',
            'Worker\\',
        ]))
        ->because('classes in PublicationApi namespace should not depend on classes from other Shared Kernel namespaces');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Worker\\'))
        ->should(new NotDependsOnTheseNamespaces([
            'Admin\\',
            'Public\\',
            'PublicationApi\\',
        ]))
        ->because('classes in Worker namespace should not depend on classes from other Shared Kernel namespaces');

    $config
        ->add($classSet, ...$rules);
};
