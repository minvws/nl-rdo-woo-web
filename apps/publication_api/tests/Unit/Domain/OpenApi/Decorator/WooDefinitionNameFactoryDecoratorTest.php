<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Decorator;

use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\Metadata\Operation;
use Mockery;
use PublicationApi\Api\Publication\Dossier\WooDecision\WooDecisionRequestDto;
use PublicationApi\Api\Publication\Dossier\WooDecision\WooDecisionResponseDto;
use PublicationApi\Domain\OpenApi\Decorator\WooDefinitionNameFactoryDecorator;
use Shared\Tests\Unit\UnitTestCase;

class WooDefinitionNameFactoryDecoratorTest extends UnitTestCase
{
    public function testInputSchemaReturnsFinalClassPart(): void
    {
        $decorated = $this->createNeverCalledDecorated();
        $decorator = new WooDefinitionNameFactoryDecorator($decorated);

        $result = $decorator->create(
            className: WooDecisionResponseDto::class,
            format: 'json',
            inputOrOutputClass: WooDecisionRequestDto::class,
            serializerContext: ['schema_type' => 'input'],
        );

        $this->assertSame('WooDecisionRequestDto', $result);
    }

    public function testNonInputSchemaDelegatesToDecoratedWithOriginalClassNames(): void
    {
        $operation = Mockery::mock(Operation::class);
        $serializerContext = ['schema_type' => 'output', 'extra' => 'value'];

        $returnValue = $this->getFaker()->word();
        $decorated = Mockery::mock(DefinitionNameFactoryInterface::class);
        $decorated->expects('create')
            ->andReturnUsing(static fn (string $className, string $format, ?string $inputOrOutputClass): string => $returnValue);

        $decorator = new WooDefinitionNameFactoryDecorator($decorated);

        $result = $decorator->create(
            className: WooDecisionResponseDto::class,
            format: 'json',
            inputOrOutputClass: 'PublicationApi\Api\WooDecisionRequestDto',
            operation: $operation,
            serializerContext: $serializerContext,
        );

        $this->assertSame($returnValue, $result);
    }

    public function testNonInputSchemaWithNullInputOrOutputClassDelegatesToDecorated(): void
    {
        $decorated = Mockery::mock(DefinitionNameFactoryInterface::class);
        $returnValue = $this->getFaker()->word();
        $decorated->expects('create')
            ->andReturnUsing(static fn (string $className, string $format, ?string $inputOrOutputClass): string => $returnValue);

        $decorator = new WooDefinitionNameFactoryDecorator($decorated);

        $result = $decorator->create(
            className: WooDecisionResponseDto::class,
            format: 'json',
            inputOrOutputClass: null,
            serializerContext: ['schema_type' => 'input'],
        );

        $this->assertSame($returnValue, $result);
    }

    public function testDecoratedReceivesUnmodifiedFormatAndSerializerContext(): void
    {
        $decorated = Mockery::mock(DefinitionNameFactoryInterface::class);
        $decorated->expects('create')
            ->with('SomeClassDto', 'jsonld', null, null, ['schema_type' => 'output', 'groups' => ['read']])
            ->andReturn('result');

        $decorator = new WooDefinitionNameFactoryDecorator($decorated);

        $decorator->create(
            // @phpstan-ignore argument.type
            className: 'SomeClassDto',
            format: 'jsonld',
            serializerContext: ['schema_type' => 'output', 'groups' => ['read']],
        );
    }

    private function createNeverCalledDecorated(): DefinitionNameFactoryInterface
    {
        $decorated = Mockery::mock(DefinitionNameFactoryInterface::class);
        $decorated->expects('create')->never();

        return $decorated;
    }
}
