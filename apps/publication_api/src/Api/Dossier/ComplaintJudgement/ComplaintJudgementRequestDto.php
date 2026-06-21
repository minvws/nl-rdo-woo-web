<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Uid\Uuid;

class ComplaintJudgementRequestDto extends AbstractDossierRequestDto
{
    public function __construct(
        public Uuid $departmentId,
        public ComplaintJudgementMainDocumentRequestDto $mainDocument,
        public ?Uuid $subjectId,
        public string $summary,
        public DossierTitle $title,
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
