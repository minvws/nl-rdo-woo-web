<?php

declare(strict_types=1);

namespace Shared\Serializer;

use InvalidArgumentException;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PlainDateException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_string;

#[AutoconfigureTag('serializer.normalizer')]
final class PlainDateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const string DEFAULT_STRING_FORMAT = 'Y-m-d';

    public function normalize($object, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($object, PlainDate::class);

        return $object->toString();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): PlainDate|string
    {
        try {
            Assert::string($data);

            return PlainDate::createFromFormat(PlainDate::DEFAULT_STRING_FORMAT, $data);
        } catch (PlainDateException) {
            return $data;
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if null is allowed)',
                $data,
                ['string'],
                $this->getDeserializationPath($context),
                true,
            );
        }
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PlainDate;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === PlainDate::class;
    }

    /**
     * @return array<string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PlainDate::class => true,
        ];
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function getDeserializationPath(array $context): ?string
    {
        if (array_key_exists('deserialization_path', $context) && is_string($context['deserialization_path'])) {
            return $context['deserialization_path'];
        }

        return null;
    }
}
