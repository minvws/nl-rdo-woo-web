<?php

declare(strict_types=1);

namespace Shared\Serializer;

use InvalidArgumentException;
use Shared\ValueObject\FileName;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

use function is_string;

#[AutoconfigureTag('serializer.normalizer')]
final class FileNameNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize($data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, FileName::class);

        return $data->toString();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): FileName
    {
        if (! is_string($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if allowed)',
                $data,
                [],
                $this->getPathFromContext($context),
                true,
            );
        }

        try {
            return FileName::create($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $invalidArgumentException->getMessage(),
                $data,
                [],
                $this->getPathFromContext($context),
                true,
            );
        }
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FileName;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === FileName::class;
    }

    /**
     * @return array<string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            FileName::class => true,
        ];
    }
}
