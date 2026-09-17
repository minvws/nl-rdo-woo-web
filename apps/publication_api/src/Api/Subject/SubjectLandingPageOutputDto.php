<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use Shared\Domain\Publication\Subject\SubjectContentTree;
use Shared\Domain\Publication\Subject\SubjectLandingPageStatus;

final readonly class SubjectLandingPageOutputDto
{
    public function __construct(
        public SubjectLandingPageStatus $status,
        public string $slug,
        public string $title,
        public string $description,
        public bool $hasVisibleContentTree,
        public SubjectContentTree $contentTree,
        public ?string $previewUrl,
    ) {
    }
}
