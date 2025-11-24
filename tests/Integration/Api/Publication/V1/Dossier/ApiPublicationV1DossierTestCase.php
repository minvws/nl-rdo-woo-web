<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Publication\V1\Dossier;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Tests\Integration\Api\Publication\V1\ApiPublicationV1TestCase;
use Symfony\Component\Uid\Uuid;

abstract class ApiPublicationV1DossierTestCase extends ApiPublicationV1TestCase
{
    abstract protected function getDossierApiUriSegment(): string;

    protected function buildUrl(Uuid|Organisation $organisation, Uuid|AbstractDossier|null $dossier = null): string
    {
        $organisationId = $organisation instanceof Uuid ? $organisation : $organisation->getId();

        if ($dossier === null) {
            return \sprintf('/api/publication/v1/organisation/%s/dossiers/%s', $organisationId, $this->getDossierApiUriSegment());
        }

        $dossierId = $dossier instanceof Uuid ? $dossier : $dossier->getId();

        return \sprintf('/api/publication/v1/organisation/%s/dossiers/%s/%s', $organisationId, $this->getDossierApiUriSegment(), $dossierId);
    }
}
