<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use Shared\Domain\Organisation\Organisation;

interface OrganisationResolverInterface
{
    /**
     * @param array<array-key, mixed> $uriVariables
     */
    public function resolve(array $uriVariables): Organisation;
}
