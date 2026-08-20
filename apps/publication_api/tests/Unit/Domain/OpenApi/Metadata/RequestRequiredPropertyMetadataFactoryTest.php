<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\AbstractDossierRequestDto;
use PublicationApi\Domain\OpenApi\Metadata\RequestRequiredPropertyMetadataFactory;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class RequestRequiredPropertyMetadataFactoryTest extends UnitTestCase
{
    public function testItMarksNonNullableConstructorPromotedPropertiesAsRequired(): void
    {
        $decorated = $this->createDecorated();
        $factory = new RequestRequiredPropertyMetadataFactory($decorated);

        $result = $factory->create($this->createDummyDossierRequestDto()::class, 'departmentId');

        self::assertTrue($result->isRequired());
    }

    public function testItDoesNotMarkDefaultedPropertiesAsRequired(): void
    {
        $decorated = $this->createDecorated();
        $factory = new RequestRequiredPropertyMetadataFactory($decorated);

        $dummyClass = $this->createDummyDossierRequestDto()::class;

        self::assertNull($factory->create($dummyClass, 'optionalWithDefault')->isRequired());
    }

    public function testItDoesMarkNullablePropertiesAsRequired(): void
    {
        $decorated = $this->createDecorated();
        $factory = new RequestRequiredPropertyMetadataFactory($decorated);

        $result = $factory->create($this->createDummyDossierRequestDto()::class, 'subjectId');

        self::assertTrue($result->isRequired());
    }

    private function createDecorated(): PropertyMetadataFactoryInterface&MockInterface
    {
        $decorated = Mockery::mock(PropertyMetadataFactoryInterface::class);
        $decorated->expects('create')->andReturn(new ApiProperty());

        return $decorated;
    }

    private function createDummyDossierRequestDto(): AbstractDossierRequestDto
    {
        $uuid = new UuidV7();

        return new class($uuid, null, 'summary', DossierTitle::create('title')) extends AbstractDossierRequestDto {
            public function __construct(
                public Uuid $departmentId,
                public ?Uuid $subjectId,
                public string $summary,
                public DossierTitle $title,
                public string $optionalWithDefault = 'x',
            ) {
                parent::__construct($departmentId, 'D-1', $subjectId, $summary, $title);
            }
        };
    }
}
