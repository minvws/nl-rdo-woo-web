<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Domain\Metadata\PaginationQueryParameterResourceMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraints as Assert;

use function iterator_to_array;

final class PaginationQueryParameterResourceMetadataFactoryTest extends UnitTestCase
{
    public function testItAddsPaginationQueryParameterToCursorPaginatedGetCollection(): void
    {
        $operation = new GetCollection(
            uriTemplate: '/department',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
        );

        $collection = $this->createCollectionWithOperations(['get_departments' => $operation]);
        $inner = $this->createInnerFactory($collection);

        $result = new PaginationQueryParameterResourceMetadataFactory($inner)->create('SomeResource');

        $resource = $result[0];
        self::assertNotNull($resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $operationsArray = iterator_to_array($operations);
        $op = $operationsArray['get_departments'];
        $parameters = $op->getParameters();

        self::assertNotNull($parameters);
        self::assertTrue($parameters->has('pagination'));
    }

    public function testItDoesNotOverrideExistingPaginationParameter(): void
    {
        $existingParam = new QueryParameter(key: 'pagination', schema: ['type' => 'string']);
        $operation = new GetCollection(
            uriTemplate: '/department',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            parameters: ['pagination' => $existingParam],
        );

        $collection = $this->createCollectionWithOperations(['get_departments' => $operation]);
        $inner = $this->createInnerFactory($collection);

        $result = new PaginationQueryParameterResourceMetadataFactory($inner)->create('SomeResource');

        $resource = $result[0];
        self::assertNotNull($resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $operationsArray = iterator_to_array($operations);
        $op = $operationsArray['get_departments'];

        self::assertSame($existingParam, $op->getParameters()?->get('pagination'));
    }

    public function testItDoesNotAddPaginationParameterToGetCollection(): void
    {
        $operation = new GetCollection(
            uriTemplate: '/department',
        );

        $collection = $this->createCollectionWithOperations(['get_departments' => $operation]);
        $inner = $this->createInnerFactory($collection);

        $result = new PaginationQueryParameterResourceMetadataFactory($inner)->create('SomeResource');

        $resource = $result[0];
        self::assertNotNull($resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $operationsArray = iterator_to_array($operations);
        $op = $operationsArray['get_departments'];

        self::assertNull($op->getParameters()?->get('pagination'));
    }

    public function testItDoesNotAddPaginationParameterToGetOperation(): void
    {
        $operation = new Get(uriTemplate: '/department/{id}');

        $collection = $this->createCollectionWithOperations(['get_department' => $operation]);
        $inner = $this->createInnerFactory($collection);

        $result = new PaginationQueryParameterResourceMetadataFactory($inner)->create('SomeResource');

        $resource = $result[0];
        self::assertNotNull($resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $operationsArray = iterator_to_array($operations);
        $op = $operationsArray['get_department'];

        self::assertNull($op->getParameters()?->get('pagination'));
    }

    public function testInjectedParameterHasWhenConstraintWithArrayTypeAndCursorCollection(): void
    {
        $operation = new GetCollection(
            uriTemplate: '/department',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
        );

        $collection = $this->createCollectionWithOperations(['get_departments' => $operation]);
        $inner = $this->createInnerFactory($collection);

        $result = new PaginationQueryParameterResourceMetadataFactory($inner)->create('SomeResource');

        $resource = $result[0];
        self::assertNotNull($resource);
        $operations = $resource->getOperations();
        self::assertNotNull($operations);

        $operationsArray = iterator_to_array($operations);
        $op = $operationsArray['get_departments'];
        $param = $op->getParameters()?->get('pagination');

        self::assertNotNull($param);
        $constraints = $param->getConstraints();
        self::assertIsArray($constraints);
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Assert\When::class, $constraints[0]);

        $when = $constraints[0];
        self::assertSame('value !== null', $when->expression);

        $inner = $when->constraints;
        self::assertIsArray($inner);
        self::assertCount(2, $inner);
        self::assertInstanceOf(Assert\Type::class, $inner[0]);
        self::assertSame('array', $inner[0]->type);
        self::assertInstanceOf(Assert\Collection::class, $inner[1]);
        self::assertFalse($inner[1]->allowExtraFields);
        self::assertFalse($inner[1]->allowMissingFields);
        self::assertArrayHasKey('cursor', $inner[1]->fields);
    }

    /**
     * @param array<string, HttpOperation> $operations
     */
    private function createCollectionWithOperations(array $operations): ResourceMetadataCollection
    {
        $resource = new ApiResource(
            operations: $operations,
        );

        return new ResourceMetadataCollection('SomeResource', [$resource]);
    }

    private function createInnerFactory(
        ResourceMetadataCollection $collection,
    ): ResourceMetadataCollectionFactoryInterface&MockInterface {
        $mock = Mockery::mock(ResourceMetadataCollectionFactoryInterface::class);
        $mock->expects('create')
            ->with('SomeResource')
            ->andReturn($collection);

        return $mock;
    }
}
