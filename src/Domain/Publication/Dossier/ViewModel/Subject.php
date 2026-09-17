<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Subject\SubjectContentTree;
use Symfony\Component\Uid\Uuid;

readonly class Subject
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $searchUrl,
        public ?string $landingPageUrl,
        public string $landingPageUrlOrSearchUrl,
        public bool $hasPublishedLandingPage,
        public ?string $landingPageTitle,
        public ?string $landingPageDescription,
        public ?SubjectContentTree $landingPageContentTree,
        public bool $hasVisibleLandingPageContentTree,
    ) {
    }
}
