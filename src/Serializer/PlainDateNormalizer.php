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

use function sprintf;

#[AutoconfigureTag('serializer.normalizer')]
final class PlainDateNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize($data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, PlainDate::class);

        return $data->toString();
    }

    public function denormalize($data, string $type, ?string $format = null, array $context = []): PlainDate
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if null is allowed)',
                $data,
                ['string'],
                $this->getPathFromContext($context),
                true,
            );
        }

        try {
            return PlainDate::createFromFormat(PlainDate::DEFAULT_STRING_FORMAT, $data);
        } catch (PlainDateException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('The data is not a valid date in format "%s"', PlainDate::DEFAULT_STRING_FORMAT),
                $data,
                ['date'],
                $this->getPathFromContext($context),
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
}
