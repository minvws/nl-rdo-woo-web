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
 * @template TDossierRequestDto of DossierRequestDtoInterface
 */
interface DossierStrategy
{
    /**
     * @return class-string<TDossier>
     */
    public function dossierType(): string;

    /**
     * @param TDossierRequestDto $dossierRequestDto
     */
    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void;

    /**
     * @param TDossierRequestDto $dossierRequestDto
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
     * @param TDossierRequestDto $dossierRequestDto
     */
    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void;
}
