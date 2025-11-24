<?php

declare(strict_types=1);

namespace Shared\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AuthMatrixExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new AuthMatrixConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('authorization_matrix', $config);
    }

    #[\Override]
    public function getAlias(): string
    {
        return 'auth_matrix';
    }
}
