<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Organisation;

use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final class OrganisationResolverTest extends ApiPublicationV1TestCase
{
    public function testGetWithUnknownOrganisationReturnsNotFound(): void
    {
        $unknownOrganisationId = self::getFaker()->uuid();

        self::createPublicationApiClient()->request(
            Request::METHOD_GET,
            sprintf('/api/publication/v1/organisation/%s/dossiers/disposition', $unknownOrganisationId),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Organisation with id %s was not found', $unknownOrganisationId),
        ]);
    }
}
