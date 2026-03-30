<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Shared\ValueObject\ExternalId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_string;

#[AutoconfigureTag('serializer.normalizer')]
final class ExternalIdNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($object, ExternalId::class);

        return $object->__toString();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): ExternalId
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if allowed)',
                $data,
                ['string'],
                $this->getDeserializationPath($context),
                true,
            );
        }

        return ExternalId::create($data);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ExternalId;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === ExternalId::class;
    }

    /**
     * @return array<string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            ExternalId::class => true,
        ];
    }

    /**
     * @param array<mixed> $context
     */
    public function getDeserializationPath(array $context): ?string
    {
        if (array_key_exists('deserialization_path', $context) && is_string($context['deserialization_path'])) {
            return $context['deserialization_path'];
        }

        return null;
    }
}
