<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject as SubjectEntity;
use Shared\Domain\Search\Query\Facet\FacetDefinitions;
use Shared\Service\Search\Model\FacetKey;

readonly class SubjectViewFactory
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
        private FacetDefinitions $facetDefinitions,
    ) {
    }

    public function make(SubjectEntity $subject): Subject
    {
        $searchUrl = $this->getSearchUrl($subject);
        $landingPageUrl = $this->getLandingPageUrl($subject);

        return new Subject(
            id: $subject->getId(),
            name: $subject->getName(),
            searchUrl: $searchUrl,
            landingPageUrl: $landingPageUrl,
            landingPageUrlOrSearchUrl: $landingPageUrl ?? $searchUrl,
            hasPublishedLandingPage: $subject->hasPublishedLandingPage(),
            landingPageTitle: $subject->getLandingPageTitle()?->toString(),
            landingPageDescription: $subject->getLandingPageDescription(),
            landingPageContentTree: $subject->getLandingPageContentTree(),
            hasVisibleLandingPageContentTree: $subject->hasVisibleLandingPageContentTree(),
        );
    }

    public function getSubjectForDossier(AbstractDossier $dossier): ?Subject
    {
        if ($dossier->getSubject() === null) {
            return null;
        }

        return $this->make($dossier->getSubject());
    }

    private function getLandingPageUrl(SubjectEntity $subject): ?string
    {
        if (! $subject->hasPublishedLandingPage()) {
            return null;
        }

        if ($subject->getLandingPageSlug() === null) {
            return null;
        }

        return $this->publicUrlGenerator->buildUrlFromRoute(
            'app_subject_landing_page',
            [
                'slug' => (string) $subject->getLandingPageSlug(),
            ],
        )->toString();
    }

    private function getSearchUrl(SubjectEntity $subject): string
    {
        return $this->publicUrlGenerator->buildUrlFromRoute(
            'app_search',
            [
                $this->facetDefinitions->get(FacetKey::SUBJECT)->getRequestParameter() => [$subject->getName()],
            ],
        )->toString();
    }
}
