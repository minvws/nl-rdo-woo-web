<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Organisation;

use PublicationApi\Api\Organisation\OrganisationResource;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class OrganisationPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetOrganisation(): void
    {
        $departmentA = DepartmentFactory::createOne([
            'name' => 'departmentA',
        ]);
        $departmentB = DepartmentFactory::createOne([
            'name' => 'departmentB',
        ]);

        $organisation = OrganisationFactory::createOne([
            'departments' => [$departmentA, $departmentB],
        ]);

        $subjectA = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => 'subjectA',
        ]);
        $subjectB = SubjectFactory::createOne([
            'organisation' => $organisation,
            'name' => 'subjectB',
        ]);

        $prefixA = DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $prefixB = DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/organisation/%s', $organisation->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $organisation->getId(),
            'name' => $organisation->getName(),
            'departments' => [
                [
                    'id' => (string) $departmentA->getId(),
                    'name' => $departmentA->getName(),
                ],
                [
                    'id' => (string) $departmentB->getId(),
                    'name' => $departmentB->getName(),
                ],
            ],
            'subjects' => [
                [
                    'id' => (string) $subjectA->getId(),
                    'name' => $subjectA->getName(),
                ],
                [
                    'id' => (string) $subjectB->getId(),
                    'name' => $subjectB->getName(),
                ],
            ],
            'prefixes' => [
                [
                    'id' => (string) $prefixA->getId(),
                    'prefix' => $prefixA->getPrefix(),
                ],
                [
                    'id' => (string) $prefixB->getId(),
                    'prefix' => $prefixB->getPrefix(),
                ],
            ],
        ];

        self::assertEquals($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(OrganisationResource::class);
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
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => 'Organisation with id invalid was not found',
        ]);
    }

    public function testGetWithUnknownOrganisation(): void
    {
        $organisationId = self::getFaker()->uuid();

        self::createPublicationApiClient()
            ->request(Request::METHOD_GET, sprintf('/api/publication/v1/organisation/%s', $organisationId));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Organisation with id %s was not found', $organisationId),
        ]);
    }

    public function testGetCollection(): void
    {
        $organisationCount = $this->getFaker()->numberBetween(1, 3);
        OrganisationFactory::createMany($organisationCount);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/organisation');
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationResource::class);
        self::assertCount($organisationCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        OrganisationFactory::createMany(5);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/organisation');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OrganisationResource::class);
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
        self::assertMatchesResourceCollectionJsonSchema(OrganisationResource::class);
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
        self::assertMatchesResourceCollectionJsonSchema(OrganisationResource::class);
        self::assertCount(1, $response->toArray());
    }
}
