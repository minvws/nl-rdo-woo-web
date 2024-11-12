<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Global twig extensions that are non-specific to the application.
 */
class AppExtension extends AbstractExtension
{
    protected AppExtensionRuntime $runtime;

    public function __construct(AppExtensionRuntime $runtime)
    {
        $this->runtime = $runtime;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('size', [$this->runtime, 'size']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_version', [$this->runtime, 'appVersion']),
            new TwigFunction('is_backend', [$this->runtime, 'isBackend']),
        ];
    }
}
