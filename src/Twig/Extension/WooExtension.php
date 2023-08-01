<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\WooExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Global twig extensions that are specific to the application (ie. domain logic).
 */
class WooExtension extends AbstractExtension
{
    protected WooExtensionRuntime $runtime;

    public function __construct(WooExtensionRuntime $runtime)
    {
        $this->runtime = $runtime;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('decision', [$this->runtime, 'decision']),
            new TwigFilter('sourceTypeIcon', [$this->runtime, 'sourceTypeIcon']),
            new TwigFilter('classification', [$this->runtime, 'classification']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_facets', [$this->runtime, 'hasFacets']),
            new TwigFunction('facet_checked', [$this->runtime, 'facetChecked']),
            new TwigFunction('facet2query', [$this->runtime, 'facet2query']),
            new TwigFunction('status_badge', [$this->runtime, 'statusBadge'], ['is_safe' => ['html']]),
            new TwigFunction('period', [$this->runtime, 'period']),
            new TwigFunction('has_thumbnail', [$this->runtime, 'hasThumbnail']),
        ];
    }
}
