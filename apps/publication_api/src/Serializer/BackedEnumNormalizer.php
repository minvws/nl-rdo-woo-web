<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use BackedEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

readonly class BackedEnumNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): int|string
    {
        Assert::isInstanceOf($data, BackedEnum::class);

        return $data->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BackedEnum;
    }

    /**
     * @return array<array-key, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [BackedEnum::class => true];
    }
}
