<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Subject;

readonly class SubjectSearchResult
{
    public function __construct(
        public string $landingPageUrl,
        public string $title,
        public ?string $description,
    ) {
    }
}
