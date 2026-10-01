<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;

/**
 * @template TDossier of AbstractDossier
 * @template TResponseDto of DossierResponseDtoInterface
 */
interface DossierResponseMapper
{
    /**
     * @param array<array-key,TDossier> $dossiers
     *
     * @return list<TResponseDto>
     */
    public function fromEntities(array $dossiers): array;

    /**
     * @param TDossier $dossier
     *
     * @return TResponseDto
     */
    public function fromEntity(AbstractDossier $dossier): DossierResponseDtoInterface;
}
