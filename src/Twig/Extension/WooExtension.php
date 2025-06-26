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
    public function __construct(protected WooExtensionRuntime $runtime)
    {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('classification', $this->runtime->classification(...)),
            new TwigFilter(
                'highlights',
                $this->runtime->filterHighlights(...),
                ['pre_escape' => 'html', 'is_safe' => ['html']],
            ),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('period', $this->runtime->period(...)),
            new TwigFunction('get_citation_type', $this->runtime->getCitationType(...)),
            new TwigFunction('query_string_without_param', $this->runtime->queryStringWithoutParam(...)),
            new TwigFunction('get_organisation_switcher', $this->runtime->getOrganisationSwitcher(...)),
            new TwigFunction('get_frontend_history', $this->runtime->getFrontendHistory(...)),
            new TwigFunction('get_backend_history', $this->runtime->getBackendHistory(...)),
            new TwigFunction('history_trans', $this->runtime->historyTranslation(...)),
            new TwigFunction('dossier_details_path', $this->runtime->dossierDetailsPath(...)),
            new TwigFunction('get_dossier_notifications', $this->runtime->getDossierNotifications(...)),
        ];
    }
}
