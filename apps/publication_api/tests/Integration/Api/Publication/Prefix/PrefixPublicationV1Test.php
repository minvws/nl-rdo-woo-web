<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Prefix;

use PublicationApi\Api\Publication\Prefix\PrefixDto;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class PrefixPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetPrefix(): void
    {
        $prefix = DocumentPrefixFactory::createOne();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $prefix->getId(),
            'organisation' => [
                'id' => (string) $prefix->getOrganisation()->getId(),
                'name' => $prefix->getOrganisation()->getName(),
            ],
            'prefix' => $prefix->getPrefix(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $prefix = DocumentPrefixFactory::createOne();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $prefix = DocumentPrefixFactory::createOne();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidPrefixParameter(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix/invalid', $organisation->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithInvalidOrganisationParameter(): void
    {
        $prefix = DocumentPrefixFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/invalid/prefix/%s', $prefix->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithOtherOrganisation(): void
    {
        $prefix = DocumentPrefixFactory::createOne();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetCollection(): void
    {
        $organisation = OrganisationFactory::createOne();

        $prefixCount = $this->getFaker()->numberBetween(1, 3);
        DocumentPrefixFactory::createMany($prefixCount, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount($prefixCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        $organisation = OrganisationFactory::createOne();

        DocumentPrefixFactory::createMany(5, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        $organisation = OrganisationFactory::createOne();

        DocumentPrefixFactory::new(['organisation' => $organisation])->create();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create();
        $cursorPrefix = DocumentPrefixFactory::new(['organisation' => $organisation])->create();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create();

        $requestParameters = sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            base64_encode((string) json_encode(['id' => (string) $cursorPrefix->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix?%s', $organisation->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        $prefix = DocumentPrefixFactory::new()->create();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s/prefix?%s', $prefix->getOrganisation()->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(1, $response->toArray());
    }

    public function testCreatePrefix(): void
    {
        $organisation = OrganisationFactory::createOne();
        $prefix = $this->getFaker()->unique()->word();

        self::assertDatabaseCount(DocumentPrefix::class, 0);

        $data = [
            'prefix' => $prefix,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);

        self::assertDatabaseCount(DocumentPrefix::class, 1);
    }

    public function testCreatePrefixWithInvalidName(): void
    {
        $organisation = OrganisationFactory::createOne();
        $prefix = $this->getFaker()->unique()->word();

        $data = [
            'prefix' => [$prefix],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
