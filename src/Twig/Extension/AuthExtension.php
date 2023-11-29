<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\AuthExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Authorization extension.
 */
class AuthExtension extends AbstractExtension
{
    protected AuthExtensionRuntime $runtime;

    public function __construct(AuthExtensionRuntime $runtime)
    {
        $this->runtime = $runtime;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('matrix_has_permission', [$this->runtime, 'hasPermission']),
        ];
    }
}
