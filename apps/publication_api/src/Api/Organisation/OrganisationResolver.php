<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class OrganisationResolver
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function resolve(array $uriVariables): Organisation
    {
        Assert::keyExists($uriVariables, 'organisationId');

        $organisationId = $uriVariables['organisationId'];
        Assert::isInstanceOf($organisationId, Uuid::class);

        $organisation = $this->organisationRepository->find($organisationId);
        if ($organisation === null) {
            throw EntityNotFoundException::for('Organisation', $organisationId);
        }

        return $organisation;
    }
}
