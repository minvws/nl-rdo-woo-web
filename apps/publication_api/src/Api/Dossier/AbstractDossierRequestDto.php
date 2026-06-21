<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDossierRequestDto
{
    public function __construct(
        public Uuid $departmentId,
        public string $dossierNumber,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
    ) {
    }
}
