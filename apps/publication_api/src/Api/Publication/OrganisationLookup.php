<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class OrganisationLookup
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
    ) {
    }

    public function find(string $organisationId): Organisation
    {
        $organisation = $this->organisationRepository->find($organisationId);
        if (! $organisation instanceof Organisation) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No organisation found'));
        }

        return $organisation;
    }
}
