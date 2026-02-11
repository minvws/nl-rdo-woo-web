<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use DateTimeImmutable;
use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Symfony\Component\Uid\Uuid;

class ComplaintJudgementRequestDto extends AbstractDossierRequestDto
{
    public function __construct(
        public Uuid $departmentId,
        public string $internalReference,
        public MainDocumentRequestDto $mainDocument,
        public string $prefix,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
        public DateTimeImmutable $dossierDate,
        public string $dossierNumber,
        public DateTimeImmutable $publicationDate,
    ) {
        parent::__construct(
            $departmentId,
            $dossierNumber,
            $internalReference,
            $prefix,
            $subjectId,
            $summary,
            $title,
        );
    }
}
