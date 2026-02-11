<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Department;

use PublicationApi\Api\Publication\Department\DepartmentDto;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Tests\Factory\DepartmentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function base64_encode;
use function json_encode;
use function sprintf;

final class DepartmentPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGetDepartment(): void
    {
        $department = DepartmentFactory::createOne();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                sprintf('/api/publication/v1/department/%s', $department->getId()),
            );

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $department->getId(),
            'name' => $department->getName(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(DepartmentDto::class);
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
    }

    public function testGetWithInvalidDepartmentParameter(): void
    {
        self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department/invalid');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertMatchesResourceItemJsonSchema(DepartmentDto::class);
    }

    public function testGetCollection(): void
    {
        $departmentCount = $this->getFaker()->numberBetween(1, 3);
        DepartmentFactory::createMany($departmentCount);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
        self::assertCount($departmentCount, $response->toArray());
    }

    public function testGetCollectionWithPaginator(): void
    {
        DepartmentFactory::createMany(5);

        $response = self::createPublicationApiClient()
            ->request(Request::METHOD_GET, '/api/publication/v1/department');

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
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
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
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
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
        self::assertCount(1, $response->toArray());
    }
}
