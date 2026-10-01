<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

enum SubjectContentTreeStatus: string
{
    case CONCEPT = 'concept';
    case PUBLISHED = 'published';
}
