<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Publication\V1\Department;

use Shared\Api\Publication\V1\Department\DepartmentDto;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Integration\Api\Publication\V1\ApiPublicationV1TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DepartmentPublicationV1Test extends ApiPublicationV1TestCase
{
    public function testGet(): void
    {
        $department = DepartmentFactory::createOne()->_real();

        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/department/%s', $department->getId()),
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
        $department = DepartmentFactory::createOne()->_real();

        static::createClient()
            ->withOptions(['headers' => [
                'Accept' => 'application/json',
            ]])
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/department/%s', $department->getId()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetWithInvalidSslUserNameReturnsUnauthorized(): void
    {
        $department = DepartmentFactory::createOne()->_real();

        static::createPublicationApiClient('invalid.example.com')
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/department/%s/', $department->getId()),
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
        DepartmentFactory::new()->create()->_real();
        DepartmentFactory::new()->create()->_real();
        $cursorDepartment = DepartmentFactory::new()->create()->_real();
        DepartmentFactory::new()->create()->_real();
        DepartmentFactory::new()->create()->_real();

        $requestParameters = \sprintf(
            'pagination[itemsPerPage]=2&pagination[cursor]=%s',
            \base64_encode((string) \json_encode(['id' => (string) $cursorDepartment->getId()])),
        );
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/department?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
        self::assertCount(2, $response->toArray());
    }

    public function testGetCollectionWithPaginatorAndInvalidCursor(): void
    {
        DepartmentFactory::createOne()->_real();

        $requestParameters = 'pagination[itemsPerPage]=2&pagination[cursor]=foo';
        $response = self::createPublicationApiClient()
            ->request(
                Request::METHOD_GET,
                \sprintf('/api/publication/v1/department?%s', $requestParameters),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DepartmentDto::class);
        self::assertCount(1, $response->toArray());
    }
}
