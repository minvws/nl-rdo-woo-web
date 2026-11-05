<?php

declare(strict_types=1);

namespace Shared\Serializer;

use InvalidArgumentException;
use Shared\ValueObject\Url;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

use function is_string;

#[AutoconfigureTag('serializer.normalizer')]
final class UrlNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize($data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, Url::class);

        return $data->toString();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): Url
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
            return Url::create($data);
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
        return $data instanceof Url;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Url::class;
    }

    /**
     * @return array<string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Url::class => true,
        ];
    }
}
