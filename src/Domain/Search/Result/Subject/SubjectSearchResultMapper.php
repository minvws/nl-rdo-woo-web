<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Subject;

use MinVWS\TypeArray\TypeArray;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Publication\Subject\SubjectRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticHighlights;
use Shared\Domain\Search\Result\HighlightMapperTrait;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;

readonly class SubjectSearchResultMapper implements SearchResultMapperInterface
{
    use HighlightMapperTrait;

    public function __construct(
        private SubjectRepository $subjectRepository,
        private SubjectViewFactory $subjectViewFactory,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::SUBJECT;
    }

    public function map(TypeArray $hit, ApplicationId $applicationId = ApplicationId::PUBLIC): ?ResultEntryInterface
    {
        $id = $hit->getStringOrNull('[_id]');
        if ($id === null) {
            return null;
        }

        $subject = $this->subjectRepository->find($id);
        if ($subject === null) {
            return null;
        }

        // The landing page URL is only available for a published landing page with a slug
        $landingPageUrl = $this->subjectViewFactory->getLandingPageUrl($subject);
        if ($landingPageUrl === null) {
            return null;
        }

        return new SubjectSearchResultEntry(
            new SubjectSearchResult(
                landingPageUrl: $landingPageUrl,
                title: $subject->getLandingPageTitle()?->toString() ?? $subject->getName(),
                description: $subject->getLandingPageDescription(),
            ),
            $this->getHighlightData($hit, $this->getHighlightPaths($subject)),
        );
    }

    /**
     * @return list<string>
     */
    private function getHighlightPaths(Subject $subject): array
    {
        $highlightPaths = ElasticHighlights::getTopLevelPaths();
        if (! $subject->hasVisibleLandingPageContentTree()) {
            return $highlightPaths;
        }

        foreach (ElasticHighlights::getContentTreePaths() as $contentTreePath) {
            $highlightPaths[] = $contentTreePath;
        }

        return $highlightPaths;
    }
}
