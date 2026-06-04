<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use Mockery;
use PublicationApi\Api\MainDocument\MainDocumentRequestDto;
use PublicationApi\Serializer\RequestBodyIgnoreIdDenormalizer;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class RequestBodyIgnoreIdDenormalizerTest extends UnitTestCase
{
    public function testDenormalizeStripsIdBeforeDelegating(): void
    {
        $expected = new stdClass();
        $title = self::getFaker()->word();

        $innerDenormalizer = Mockery::mock(DenormalizerInterface::class);
        $innerDenormalizer->expects('denormalize')
            ->with(['title' => $title], MainDocumentRequestDto::class, null, [])
            ->andReturn($expected);

        $denormalizer = new RequestBodyIgnoreIdDenormalizer();
        $denormalizer->setDenormalizer($innerDenormalizer);

        $data = [
            'id' => self::getFaker()->uuid(),
            'title' => $title,
        ];
        $result = $denormalizer->denormalize($data, MainDocumentRequestDto::class);

        self::assertSame($expected, $result);
    }

    public function testSupportsDenormalizationWhenDataHasIdAndClassHasNoIdProperty(): void
    {
        $denormalizer = new RequestBodyIgnoreIdDenormalizer();

        $data = [
            'id' => self::getFaker()->uuid(),
            'title' => self::getFaker()->word(),
        ];
        $result = $denormalizer->supportsDenormalization($data, MainDocumentRequestDto::class);

        self::assertTrue($result);
    }

    public function testDoesNotSupportDenormalizationWhenPreserveIdInBodyIsTrue(): void
    {
        $denormalizer = new RequestBodyIgnoreIdDenormalizer();

        $data = ['id' => self::getFaker()->uuid()];
        $result = $denormalizer->supportsDenormalization(
            $data,
            MainDocumentRequestDto::class,
            context: ['preserve_id_in_body' => true],
        );

        self::assertFalse($result);
    }

    public function testDoesNotSupportDenormalizationWhenDataHasNoId(): void
    {
        $denormalizer = new RequestBodyIgnoreIdDenormalizer();

        $data = ['title' => self::getFaker()->word()];
        $result = $denormalizer->supportsDenormalization($data, MainDocumentRequestDto::class);

        self::assertFalse($result);
    }

    public function testDoesNotSupportDenormalizationWhenDataIsNotAnArray(): void
    {
        $denormalizer = new RequestBodyIgnoreIdDenormalizer();

        $result = $denormalizer->supportsDenormalization('not-an-array', MainDocumentRequestDto::class);

        self::assertFalse($result);
    }
}
