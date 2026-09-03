<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Symfony\Component\Uid\Uuid;

readonly class Subject
{
    /**
     * @param list<array<string, mixed>>|null $landingPageContentTree
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $searchUrl,
        public ?string $landingPageUrl,
        public string $landingPageUrlOrSearchUrl,
        public bool $hasPublishedLandingPage,
        public ?string $landingPageTitle,
        public ?string $landingPageDescription,
        public ?array $landingPageContentTree,
        public bool $hasVisibleLandingPageContentTree,
    ) {
    }
}
