<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Department;

use PublicationApi\Api\Department\DepartmentResource;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class DepartmentPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetDepartment(): void
    {
        $organisationA = OrganisationFactory::createOne();
        $organisationB = OrganisationFactory::createOne();
        $department = DepartmentFactory::createOne(['organisations' => [$organisationA, $organisationB]]);

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department/%s', $department->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $department->getId(),
            'name' => $department->getName(),
            'organisations' => [
                [
                    'id' => $organisationA->getId()->toRfc4122(),
                    'name' => $organisationA->getName(),
                ],
                [
                    'id' => $organisationB->getId()->toRfc4122(),
                    'name' => $organisationB->getName(),
                ],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(DepartmentResource::class);
    }

    public function testGetDepartmentsWithSpecialUrlCharactersDoesNotFail(): void
    {
        self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                '/api/publication/v1/department?y%5B%C2%9D%C3%84%F0%AA%89%93%C3%9D%10%F1%B3%B7%AB%C3%BB%F1%A5%82%AA5%0A-=',
            );

        self::assertResponseIsSuccessful();
    }

    public function testGetWithoutSslUserNameReturnsUnauthorized(): void
    {
        $department = DepartmentFactory::createOne();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department/%s', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $department = DepartmentFactory::createOne();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department/%s/', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertJsonEquals([
            'type' => 'errors#authentication-failed',
            'title' => 'Authentication Failed',
            'status' => Response::HTTP_UNAUTHORIZED,
            'detail' => 'Client Certificate Common Name is not whitelisted. Please read the documentation or contact your system administrator.',
        ]);
    }

    public function testGetWithInvalidDepartmentParameter(): void
    {
        self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department/invalid');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => 'Department with id invalid was not found',
        ]);
    }

    public function testGetWithUnknownDepartment(): void
    {
        $departmentId = self::getFaker()->uuid();

        self::createPublicationApiClient()
            ->request(Request::METHOD_GET, sprintf('/api/publication/v1/department/%s', $departmentId));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Department with id %s was not found', $departmentId),
        ]);
    }

    public function testGetCollection(): void
    {
        $departmentCount = $this->getFaker()->numberBetween(1, 3);
        DepartmentFactory::createMany($departmentCount);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentResource::class);
        self::assertCount($departmentCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        DepartmentFactory::createMany(5);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentResource::class);
        self::assertCount(5, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndCursor(): void
    {
        DepartmentFactory::new()->create();
        DepartmentFactory::new()->create();
        $cursorDepartment = DepartmentFactory::new()->create();
        DepartmentFactory::new()->create();
        DepartmentFactory::new()->create();

        $requestParameters = sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            base64_encode((string) json_encode(['id' => (string) $cursorDepartment->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentResource::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        DepartmentFactory::createOne();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentResource::class);
        self::assertCount(1, $response->toArray());
    }
}
