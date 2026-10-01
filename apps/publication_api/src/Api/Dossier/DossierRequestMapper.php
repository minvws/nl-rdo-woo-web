<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;

/**
 * @template TDossier of AbstractDossier
 * @template TRequestDto of DossierRequestDtoInterface
 */
interface DossierRequestMapper
{
    /**
     * @param TRequestDto $dossierRequestDto
     *
     * @return TDossier
     */
    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): AbstractDossier;

    /**
     * @param TDossier $dossier
     * @param TRequestDto $dossierRequestDto
     */
    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void;
}
