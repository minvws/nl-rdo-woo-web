<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use BackedEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

readonly class BackedEnumNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): int|string
    {
        Assert::isInstanceOf($object, BackedEnum::class);

        return $object->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $data instanceof BackedEnum;
    }

    /**
     * @return true[]
     */
    public function getSupportedTypes(?string $format): array
    {
        return [BackedEnum::class => true];
    }
}
