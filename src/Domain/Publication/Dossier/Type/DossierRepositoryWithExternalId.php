<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\ValueObject\ExternalId;

/**
 * @template TDossier of AbstractDossier
 */
interface DossierRepositoryWithExternalId
{
    /**
     * @return ?TDossier
     */
    public function findByOrganisationAndExternalId(Organisation $organisation, ExternalId $externalId): ?AbstractDossier;
}
