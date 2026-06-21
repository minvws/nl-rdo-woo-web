<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Shared\Serializer\PathFromContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class UuidNormalizer implements DenormalizerInterface
{
    use PathFromContext;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Uuid
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'Invalid string format',
                $data,
                [],
                $this->getPathFromContext($context),
                true,
            );
        }

        try {
            return Uuid::fromString($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'Invalid uuid format',
                $data,
                [],
                $this->getPathFromContext($context),
                true,
                $invalidArgumentException->getCode(),
                $invalidArgumentException,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Uuid::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Uuid::class => true,
        ];
    }
}
