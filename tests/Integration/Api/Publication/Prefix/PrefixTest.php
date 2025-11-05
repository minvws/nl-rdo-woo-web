<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Publication\Prefix;

use App\Api\Publication\V1\Prefix\PrefixDto;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use App\Tests\Integration\Api\Publication\PublicationApiTestCase;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PrefixTest extends PublicationApiTestCase
{
    use IntegrationTestTrait;

    public function testGet(): void
    {
        $prefix = DocumentPrefixFactory::createOne()->_real();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $prefix->getId(),
            'organisation' => [
                'id' => (string) $prefix->getOrganisation()->getId(),
            ],
            'prefix' => $prefix->getPrefix(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $prefix = DocumentPrefixFactory::createOne()->_real();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $prefix = DocumentPrefixFactory::createOne()->_real();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidPrefixParameter(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix/invalid', $organisation->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithInvalidOrganisationParameter(): void
    {
        $prefix = DocumentPrefixFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/invalid/prefix/%s', $prefix->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetWithOtherOrganisation(): void
    {
        $prefix = DocumentPrefixFactory::createOne()->_real();

        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix/%s', $prefix->getOrganisation()->getId(), $prefix->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);
    }

    public function testGetCollection(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        $prefixCount = $this->getFaker()->numberBetween(1, 3);
        DocumentPrefixFactory::createMany($prefixCount, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount($prefixCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        DocumentPrefixFactory::createMany(5, ['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        DocumentPrefixFactory::new(['organisation' => $organisation])->create()->_real();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create()->_real();
        $cursorPrefix = DocumentPrefixFactory::new(['organisation' => $organisation])->create()->_real();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create()->_real();
        DocumentPrefixFactory::new(['organisation' => $organisation])->create()->_real();

        $requestParameters = \sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            \base64_encode((string) \json_encode(['id' => (string) $cursorPrefix->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix?%s', $organisation->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        $prefix = DocumentPrefixFactory::new()->create()->_real();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix?%s', $prefix->getOrganisation()->getId(), $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(PrefixDto::class);
        self::assertCount(1, $response->toArray());
    }

    public function testCreatePrefix(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $prefix = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'prefix' => $prefix,
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(PrefixDto::class);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );
        self::assertCount(1, $response->toArray());
    }

    public function testCreatePrefixWithInvalidName(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $prefix = $this->getFaker()->unique()->word();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
            );
        self::assertCount(0, $response->toArray());

        $data = [
            'prefix' => [$prefix],
        ];
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_POST,
                \sprintf('/api/publication/v1/organisation/%s/prefix', $organisation->getId()),
                [
                    'json' => $data,
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
