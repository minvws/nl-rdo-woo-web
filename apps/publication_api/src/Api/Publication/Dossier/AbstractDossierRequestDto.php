<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier;

use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Validator\EntityExists;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDossierRequestDto
{
    public function __construct(
        #[EntityExists(Department::class, 'department')]
        public Uuid $departmentId,
        public string $dossierNumber,
        public string $internalReference,
        public string $prefix,
        #[EntityExists(Subject::class, 'subject')]
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
    ) {
    }
}
