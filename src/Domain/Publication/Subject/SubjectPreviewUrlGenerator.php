<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Shared\Controller\Public\SubjectLandingPageController;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;

readonly class SubjectPreviewUrlGenerator
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    public function generatePreviewUrl(Subject $subject): ?Url
    {
        if ($subject->getLandingPageStatus() !== SubjectLandingPageStatus::CONCEPT) {
            return null;
        }

        return $this->publicUrlGenerator->buildUrlFromRoute(SubjectLandingPageController::ROUTE_NAME_SUBJECT_LANDING_PAGE_PREVIEW, [
            'id' => $subject->getId()->toRfc4122(),
            'previewToken' => $subject->getLandingPagePreviewToken()->toRfc4122(),
        ]);
    }
}
