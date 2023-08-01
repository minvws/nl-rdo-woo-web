<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Citation;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DateRangeConverter;
use App\Service\Search\Model\Facet;
use App\Service\Storage\ThumbnailStorageService;
use App\SourceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class WooExtensionRuntime implements RuntimeExtensionInterface
{
    protected RequestStack $requestStack;
    protected ThumbnailStorageService $storageService;

    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, ThumbnailStorageService $storageService, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->storageService = $storageService;
        $this->translator = $translator;
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
        switch ($status) {
            case Dossier::STATUS_CONCEPT:
                $color = 'secondary';
                break;
            case Dossier::STATUS_COMPLETED:
                $color = 'dark';
                break;
            case Dossier::STATUS_PREVIEW:
                $color = 'info text-dark';
                break;
            case Dossier::STATUS_PUBLISHED:
                $color = 'success';
                break;
            case Dossier::STATUS_RETRACTED:
                $color = 'danger';
                break;
            default:
                $color = 'secondary';
        }

        return "<span class=\"badge bg-{$color}\">" . $this->translator->trans($status) . '</span>';
    }

    /**
     * Returns textual representation of a decision.
     */
    public function decision(string $value): string
    {
        switch ($value) {
            case Dossier::DECISION_ALREADY_PUBLIC:
                return 'Reeds gepubliceerd';
            case Dossier::DECISION_PUBLIC:
                return 'Openbaar';
            case Dossier::DECISION_NOT_PUBLIC:
                return 'Niet openbaar';
            case Dossier::DECISION_NOTHING_FOUND:
                return 'Niets gevonden';
            case Dossier::DECISION_PARTIAL_PUBLIC:
                return 'Gedeeltelijk gepubliceerd';

            default:
                return 'Onbekend';
        }
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
        foreach (Facet::getQueryMapping() as $queryKey) {
            if ($request->query->has($queryKey)) {
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
        return Facet::getQueryVarForFacet($facet);
    }
}
