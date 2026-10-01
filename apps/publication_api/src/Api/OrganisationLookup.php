<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Symfony\Component\Uid\Uuid;

readonly class OrganisationLookup
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
    ) {
    }

    public function find(Uuid $organisationId): Organisation
    {
        $organisation = $this->organisationRepository->find($organisationId);
        if (! $organisation instanceof Organisation) {
            throw EntityNotFoundException::for('Organisation', $organisationId);
        }

        return $organisation;
    }
}
