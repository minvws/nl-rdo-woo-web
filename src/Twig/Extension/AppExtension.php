<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

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

    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', [$this->runtime, 'isInstanceOf']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', [$this->runtime, 'basename']),
            new TwigFilter('size', [$this->runtime, 'size']),
            new TwigFilter('carbon', [$this->runtime, 'carbon']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('choice_attr', [$this->runtime, 'getChoiceAttribute']),
            new TwigFunction('app_version', [$this->runtime, 'appVersion']),
            new TwigFunction('die', [$this->runtime, 'dieTwig']),
            new TwigFunction('is_backend', [$this->runtime, 'isBackend']),
        ];
    }
}
