<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\ConstraintViolationList;

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
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        if (! $organisation->getId()->equals($dossier->getOrganisation()->getId())) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        return $dossier;
    }
}
