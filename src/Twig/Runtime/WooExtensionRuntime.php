<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Citation;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use App\Domain\Publication\History\History;
use App\Service\DateRangeConverter;
use App\Service\DocumentUploadQueue;
use App\Service\HistoryService;
use App\Service\Search\Query\Facet\FacetTwigService;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Security\OrganisationSwitcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class WooExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private FacetTwigService $facetService,
        private DocumentUploadQueue $uploadQueue,
        private OrganisationSwitcher $organisationSwitcher,
        private HistoryService $historyService,
        private DossierPathHelper $dossierPathHelper,
    ) {
    }

    /**
     * Returns true if the given key has the given value in the request.
     */
    public function facetChecked(string $key, string $value): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (! $request) {
            return false;
        }

        $facets = $request->query->all($key);
        if (! $facets) {
            return false;
        }

        if (! is_array($facets)) {
            return $facets == $value;
        }

        foreach ($facets as $facet) {
            /** @var string $facet */
            if (urldecode($facet) == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the classification of a citation.
     */
    public function classification(string $citation): string
    {
        return Citation::toClassification($citation);
    }

    /**
     * Returns a textual representation of a date range (ie: 01-02-2011 - 01-03-2011 => februari - maart 2011).
     */
    public function period(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): string
    {
        return DateRangeConverter::convertToString($from, $to);
    }

    /**
     * Converts a facet constant to its query variable. For instance: source => src, subject => sub etc.
     */
    public function facet2query(string $facet): string
    {
        return $this->facetService->getParamKeyByFacetName($facet);
    }

    public function getCitationType(string $citation): string
    {
        return Citation::getCitationType($citation);
    }

    public function queryStringWithoutParam(string $queryParam, string $value): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (! $request) {
            return '';
        }

        $queryString = strval($request->getQueryString());
        parse_str($queryString, $currentParams);
        parse_str($queryParam, $paramToRemove);
        $paramKeyToRemove = key($paramToRemove);

        $currentParamValue = $currentParams[$paramKeyToRemove] ?? null;
        if ($currentParamValue === null) {
            return $queryString;
        }

        if (is_array($currentParamValue)) {
            if (array_is_list($currentParamValue)) {
                foreach ($currentParamValue as $paramSubKey => $paramSubValue) {
                    if ($paramSubValue === $value) {
                        unset($currentParams[$paramKeyToRemove][$paramSubKey]);
                        break;
                    }
                }
            } else {
                /** @var array<string, array<string, string>> $paramToRemove */
                $paramSubKey = key($paramToRemove[$paramKeyToRemove]);
                unset($currentParams[$paramKeyToRemove][$paramSubKey]);
            }
        } else {
            unset($currentParams[$paramKeyToRemove]);
        }

        $currentParams = array_filter($currentParams);

        $query = http_build_query($currentParams);
        $query = preg_replace('/%5B\d+%5D/imU', '%5B%5D', $query);

        return strval($query);
    }

    /**
     * @return string[]
     */
    public function getUploadQueue(WooDecision $dossier): array
    {
        return $this->uploadQueue->getFilenames($dossier);
    }

    public function filterHighlights(string $input): string
    {
        return str_replace(
            [QueryGenerator::HL_START, QueryGenerator::HL_END],
            ['<strong>', '</strong>'],
            $input,
        );
    }

    public function getOrganisationSwitcher(): OrganisationSwitcher
    {
        return $this->organisationSwitcher;
    }

    /**
     * @return History[]|array
     */
    public function getFrontendHistory(string $type, string $identifier): array
    {
        return $this->historyService->getHistory($type, $identifier, HistoryService::MODE_PUBLIC);
    }

    /**
     * @return History[]|array
     */
    public function getBackendHistory(string $type, string $identifier): array
    {
        return $this->historyService->getHistory($type, $identifier, HistoryService::MODE_PRIVATE);
    }

    public function historyTranslation(History $entry, string $mode = HistoryService::MODE_PUBLIC): string
    {
        return $this->historyService->translate($entry, $mode);
    }

    public function dossierDetailsPath(AbstractDossier|DossierReference $dossier): string
    {
        return $this->dossierPathHelper->getDetailsPath($dossier);
    }
}
