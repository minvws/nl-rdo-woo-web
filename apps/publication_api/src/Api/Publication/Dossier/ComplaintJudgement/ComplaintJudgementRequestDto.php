<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use PublicationApi\Api\Publication\Dossier\AbstractDossierRequestDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class ComplaintJudgementRequestDto extends AbstractDossierRequestDto
{
    public function __construct(
        public Uuid $departmentId,
        public MainDocumentRequestDto $mainDocument,
        public ?Uuid $subjectId,
        public string $summary,
        public string $title,
        public PlainDate $dossierDate,
        public string $dossierNumber,
        public PlainDate $publicationDate,
    ) {
        parent::__construct(
            $departmentId,
            $dossierNumber,
            $subjectId,
            $summary,
            $title,
        );
    }
}
