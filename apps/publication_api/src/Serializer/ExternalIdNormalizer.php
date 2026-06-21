<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Shared\Serializer\PathFromContext;
use Shared\ValueObject\ExternalId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class ExternalIdNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, ExternalId::class);

        return $data->toString();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ExternalId
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if allowed)',
                $data,
                [TypeIdentifier::STRING->value],
                $this->getPathFromContext($context),
                true,
            );
        }

        try {
            return ExternalId::create($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $invalidArgumentException->getMessage(),
                $data,
                [],
                $this->getPathFromContext($context),
                true,
                $invalidArgumentException->getCode(),
                $invalidArgumentException,
            );
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ExternalId;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === ExternalId::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ExternalId::class => true,
        ];
    }
}
