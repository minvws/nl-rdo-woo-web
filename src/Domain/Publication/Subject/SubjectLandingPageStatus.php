<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

enum SubjectLandingPageStatus: string
{
    case CONCEPT = 'concept';
    case PUBLISHED = 'published';
}
