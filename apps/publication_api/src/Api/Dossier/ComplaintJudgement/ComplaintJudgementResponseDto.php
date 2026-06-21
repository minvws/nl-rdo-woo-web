<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Organisation\OrganisationResponseDto;
use PublicationApi\Api\Subject\SubjectResponse;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

final class ComplaintJudgementResponseDto
{
    final public function __construct(
        public Uuid $id,
        public ?ExternalId $externalId,
        public OrganisationResponseDto $organisation,
        public string $dossierNumber,
        public DossierTitle $title,
        public string $summary,
        public ?SubjectResponse $subject,
        public DepartmentResponseDto $department,
        public ?PlainDate $publicationDate,
        public DossierStatus $status,
        public ComplaintJudgementMainDocumentResponseDto $mainDocument,
        public PlainDate $dossierDate,
        #[SerializedName('_links')]
        public LinkCollection $halLinks,
    ) {
    }
}
