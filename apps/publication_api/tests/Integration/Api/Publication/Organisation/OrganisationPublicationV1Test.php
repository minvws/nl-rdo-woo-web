<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Organisation;

use PublicationApi\Api\Publication\Organisation\OrganisationDto;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Tests\Factory\OrganisationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class OrganisationPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $organisation->getId(),
            'name' => $organisation->getName(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(OrganisationDto::class);
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $organisation = OrganisationFactory::createOne();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s', $organisation->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $organisation = OrganisationFactory::createOne();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/', $organisation->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidOrganisationParameter(): void
    {
        self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/organisation/invalid');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(OrganisationDto::class);
    }

    public function testGetCollection(): void
    {
        $organisationCount = $this->getFaker()->numberBetween(1, 3);
        OrganisationFactory::createMany($organisationCount);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/organisation');
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationDto::class);
        self::assertCount($organisationCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        OrganisationFactory::createMany(5);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/organisation');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationDto::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        OrganisationFactory::new()->create();
        OrganisationFactory::new()->create();
        $cursorOrganisation = OrganisationFactory::new()->create();
        OrganisationFactory::new()->create();
        OrganisationFactory::new()->create();

        $requestParameters = sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            base64_encode((string) json_encode(['id' => (string) $cursorOrganisation->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationDto::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        OrganisationFactory::createOne();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationDto::class);
        self::assertCount(1, $response->toArray());
    }
}
