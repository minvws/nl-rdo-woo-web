<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Citation;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\DateRangeConverter;
use App\Service\DocumentUploadQueue;
use App\Service\Search\Query\Facet\FacetMappingService;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Storage\ThumbnailStorageService;
use App\SourceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WooExtensionRuntime implements RuntimeExtensionInterface
{
    protected RequestStack $requestStack;
    protected ThumbnailStorageService $storageService;
    protected DocumentRepository $documentRepository;
    protected UrlGeneratorInterface $urlGenerator;
    protected TranslatorInterface $translator;

    public function __construct(
        RequestStack $requestStack,
        ThumbnailStorageService $storageService,
        TranslatorInterface $translator,
        DocumentRepository $documentRepository,
        UrlGeneratorInterface $urlGenerator,
        private readonly FacetMappingService $facetMapping,
        private readonly DocumentUploadQueue $uploadQueue,
    ) {
        $this->requestStack = $requestStack;
        $this->storageService = $storageService;
        $this->translator = $translator;
        $this->documentRepository = $documentRepository;
        $this->urlGenerator = $urlGenerator;
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
    public function statusBadge(string $status): string
    {
        $color = match ($status) {
            Dossier::STATUS_SCHEDULED, Dossier::STATUS_PREVIEW, Dossier::STATUS_PUBLISHED => 'bhr-badge--green',
            Dossier::STATUS_RETRACTED => 'bhr-badge--red',
            default => 'bhr-badge--purple',
        };

        return "<span class=\"bhr-badge {$color}\">" . $this->translator->trans($status) . '</span>';
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
        foreach ($this->facetMapping->getAll() as $defition) {
            if ($request->query->has($defition->getQueryParam())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the given document and pagenr has a thumbnail. For non-paged documents (like audio), pageNr 0 can be used.
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
        return $this->facetMapping->getFacetByKey($facet)->getQueryParam();
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
            if ($dossier->getStatus() == Dossier::STATUS_PUBLISHED) {
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
            if ($dossier->getStatus() == Dossier::STATUS_PUBLISHED) {
                return $this->urlGenerator->generate('app_document_detail', [
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
}
