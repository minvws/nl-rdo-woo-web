<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier;

use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
abstract class AbstractDossierDto
{
    public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $prefix,
        public string $dossierNumber,
        public string $internalReference,
        public ?string $title,
        public string $summary,
        public ?string $subject,
        public ?\DateTimeImmutable $publicationDate,
        public DossierStatus $status,
    ) {
    }
}
