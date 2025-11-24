<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Messenger;

use Shared\Domain\Messenger\LegacyNamespaceNormalizer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class LegacyNamespaceNormalizerTest extends UnitTestCase
{
    private LegacyNamespaceNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new LegacyNamespaceNormalizer();
    }

    public function testSetSerializerWithValidDenormalizer(): void
    {
        $serializer = \Mockery::mock(SerializerInterface::class, DenormalizerInterface::class);

        $this->normalizer->setSerializer($serializer);

        self::assertSame($serializer, $this->normalizer->getSerializer());
    }

    public function testSetSerializerWithInvalidSerializerThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $invalidSerializer = \Mockery::mock(SerializerInterface::class);

        $this->normalizer->setSerializer($invalidSerializer);
    }

    public function testGetSerializerThrowsExceptionWhenNotSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->normalizer->getSerializer();
    }

    public function testDenormalizeConvertsLegacyAppNamespaceToShared(): void
    {
        $serializer = \Mockery::mock(SerializerInterface::class, DenormalizerInterface::class);
        $this->normalizer->setSerializer($serializer);

        $data = ['id' => '123'];
        $legacyType = 'App\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $expectedType = 'Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $format = 'json';
        $context = [];

        $expectedObject = new \stdClass();

        $serializer
            ->shouldReceive('denormalize')
            ->once()
            ->with(
                $data,
                $expectedType,
                $format,
                ['legacy_namespace_normalized' => [$expectedType => true]]
            )
            ->andReturn($expectedObject);

        $result = $this->normalizer->denormalize($data, $legacyType, $format, $context);

        self::assertSame($expectedObject, $result);
    }

    public function testDenormalizeDoesNotConvertNonLegacyNamespace(): void
    {
        $serializer = \Mockery::mock(SerializerInterface::class, DenormalizerInterface::class);
        $this->normalizer->setSerializer($serializer);

        $data = ['id' => '123'];
        $type = 'Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $format = 'json';
        $context = [];

        $expectedObject = new \stdClass();

        $serializer
            ->shouldReceive('denormalize')
            ->once()
            ->with(
                $data,
                $type,
                $format,
                ['legacy_namespace_normalized' => [$type => true]]
            )
            ->andReturn($expectedObject);

        $result = $this->normalizer->denormalize($data, $type, $format, $context);

        self::assertSame($expectedObject, $result);
    }

    public function testDenormalizeDoesNotConvertWhenNewClassDoesNotExist(): void
    {
        $serializer = \Mockery::mock(SerializerInterface::class, DenormalizerInterface::class);
        $this->normalizer->setSerializer($serializer);

        $data = ['data' => 'test'];
        $legacyType = 'App\NonExistent\Class';
        $format = 'json';
        $context = [];

        $serializer
            ->shouldReceive('denormalize')
            ->once()
            ->with(
                $data,
                $legacyType,
                $format,
                ['legacy_namespace_normalized' => [$legacyType => true]]
            )
            ->andReturn(new \stdClass());

        $this->normalizer->denormalize($data, $legacyType, $format, $context);
    }

    public function testSupportsDenormalizationReturnsTrueForLegacyAppNamespace(): void
    {
        $type = 'App\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $context = [];

        $result = $this->normalizer->supportsDenormalization([], $type, null, $context);

        self::assertTrue($result);
    }

    public function testSupportsDenormalizationReturnsFalseForSharedNamespace(): void
    {
        $type = 'Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $context = [];

        $result = $this->normalizer->supportsDenormalization([], $type, null, $context);

        self::assertFalse($result);
    }

    public function testSupportsDenormalizationReturnsFalseForNonAppNamespace(): void
    {
        $type = 'SomeOther\Namespace\Class';
        $context = [];

        $result = $this->normalizer->supportsDenormalization([], $type, null, $context);

        self::assertFalse($result);
    }

    public function testSupportsDenormalizationReturnsFalseWhenAlreadyNormalized(): void
    {
        $convertedType = 'Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand';
        $context = ['legacy_namespace_normalized' => [$convertedType => true]];

        $result = $this->normalizer->supportsDenormalization([], $convertedType, null, $context);

        self::assertFalse($result);
    }

    public function testSupportsDenormalizationReturnsFalseWhenConvertedClassDoesNotExist(): void
    {
        $type = 'App\NonExistent\Class';
        $context = [];

        $result = $this->normalizer->supportsDenormalization([], $type, null, $context);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsObjectWithNull(): void
    {
        $result = $this->normalizer->getSupportedTypes(null);

        self::assertSame(['object' => null], $result);
    }

    public function testGetSupportedTypesWithFormat(): void
    {
        $result = $this->normalizer->getSupportedTypes('json');

        self::assertSame(['object' => null], $result);
    }
}
