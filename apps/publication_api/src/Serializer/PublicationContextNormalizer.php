<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Shared\Serializer\PathFromContext;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class PublicationContextNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, PublicationContext::class);

        return $data->toString();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): PublicationContext
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
            return PublicationContext::fromString($data);
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
        return $data instanceof PublicationContext;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === PublicationContext::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PublicationContext::class => true,
        ];
    }
}
