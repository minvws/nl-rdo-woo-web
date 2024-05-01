<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Citation;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\History;
use App\Repository\DocumentRepository;
use App\Service\DateRangeConverter;
use App\Service\DocumentUploadQueue;
use App\Service\HistoryService;
use App\Service\Search\Query\Facet\FacetTwigService;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Security\OrganisationSwitcher;
use App\Service\Storage\ThumbnailStorageService;
use App\SourceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WooExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ThumbnailStorageService $storageService,
        private readonly Translator $translator,
        private readonly DocumentRepository $documentRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FacetTwigService $facetService,
        private readonly DocumentUploadQueue $uploadQueue,
        private readonly OrganisationSwitcher $organisationSwitcher,
        private readonly HistoryService $historyService,
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
     * Returns badge based on status.
     *
     * @TODO: Remove as this uses bootstrap
     */
    public function statusBadge(DossierStatus $status): string
    {
        $color = match ($status) {
            DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED => 'bhr-badge--green',
            default => 'bhr-badge--purple',
        };

        return "<span class=\"bhr-badge {$color}\">" . $this->translator->trans($status->value) . '</span>';
    }

    /**
     * Returns textual representation of a decision.
     */
    public function decision(string $value): string
    {
        return match ($value) {
            Dossier::DECISION_ALREADY_PUBLIC => 'Reeds gepubliceerd',
            Dossier::DECISION_PUBLIC => 'Openbaar',
            Dossier::DECISION_NOT_PUBLIC => 'Niet openbaar',
            Dossier::DECISION_NOTHING_FOUND => 'Niets gevonden',
            Dossier::DECISION_PARTIAL_PUBLIC => 'Deels openbaar',
            default => 'Onbekend',
        };
    }

    /**
     * Returns icon class for a given source type.
     */
    public function sourceTypeIcon(string $value): string
    {
        return SourceType::getIcon($value);
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
     * Returns true if the given query string has any facets.
     */
    public function hasFacets(Request $request): bool
    {
        return $this->facetService->containsFacets($request->query);
    }

    /**
     * Returns true if the given document and pagenr has a thumbnail. For non-paged documents pageNr 0 can be used.
     */
    public function hasThumbnail(Document $document, int $pageNr): bool
    {
        return $this->storageService->exists($document, $pageNr);
    }

    /**
     * Converts a facet constant to its query variable. For instance: source => src, subject => sub etc.
     */
    public function facet2query(string $facet): string
    {
        return $this->facetService->getParamKeyByFacetName($facet);
    }

    /**
     * Returns true if the given link is actually a document ID (ie: PREFIX-12345) and the given dossier is set to published.
     */
    public function isDocumentLink(string $link): bool
    {
        /** @var Document|null $document */
        $document = $this->documentRepository->findOneBy(['documentNr' => $link]);
        if (is_null($document)) {
            return false;
        }

        // If we find a dossier with status published, we can return true
        foreach ($document->getDossiers() as $dossier) {
            if ($dossier->getStatus()->isPublished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the link from the given document ID found in the link. If the link is not an existing document id, it will be returned as is.
     */
    public function generateDocumentLink(string $link): string
    {
        /** @var Document|null $document */
        $document = $this->documentRepository->findOneBy(['documentNr' => $link]);
        if (is_null($document)) {
            return $link;
        }

        // If we find a dossier with status published, we can return true
        foreach ($document->getDossiers() as $dossier) {
            if ($dossier->getStatus()->isPublished()) {
                return $this->urlGenerator->generate('app_document_detail', [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'documentId' => $document->getDocumentNr(),
                ]);
            }
        }

        return $link;
    }

    public function getCitationType(string $citation): string
    {
        return Citation::getCitationType($citation);
    }

    /**
     * @param array<string, string> $params
     */
    public function getQuerystringWithParams(array $params): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (! $request) {
            return '';
        }

        $queryString = strval($request->getQueryString());
        parse_str($queryString, $currentParams);

        foreach ($params as $key => $value) {
            $currentParams[$key] = $value;
        }

        $query = http_build_query($currentParams);
        $query = preg_replace('/%5B\d+%5D/imU', '%5B%5D', $query);

        return strval($query);
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
    public function getUploadQueue(Dossier $dossier): array
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

    /**
     * @return History[]|array
     */
    public function getHistory(string $type, string $identifier): array
    {
        return $this->historyService->getHistory($type, $identifier, HistoryService::MODE_BOTH);
    }

    public function historyTranslation(History $entry, string $mode = HistoryService::MODE_PUBLIC): string
    {
        return $this->historyService->translate($entry, $mode);
    }
}
