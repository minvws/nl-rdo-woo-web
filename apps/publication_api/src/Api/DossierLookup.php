<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\ValueObject\ExternalId;

readonly class DossierLookup
{
    /**
     * @template TDossier of AbstractDossier
     *
     * @param DossierRepositoryWithExternalId<TDossier> $dossierRepositoryWithExternalId
     *
     * @return TDossier
     */
    public function find(
        DossierRepositoryWithExternalId $dossierRepositoryWithExternalId,
        Organisation $organisation,
        ExternalId $externalId,
    ): AbstractDossier {
        $dossier = $dossierRepositoryWithExternalId->findByOrganisationAndExternalId($organisation, $externalId);
        if (! $dossier instanceof AbstractDossier) {
            throw EntityNotFoundException::for('Dossier', $externalId);
        }

        if (! $organisation->getId()->equals($dossier->getOrganisation()->getId())) {
            throw EntityNotFoundException::for('Dossier', $externalId);
        }

        return $dossier;
    }
}
