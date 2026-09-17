<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use ArrayObject;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class LinkCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return ArrayObject<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): ArrayObject
    {
        Assert::isInstanceOf($data, LinkCollection::class);

        return new ArrayObject($this->normalizeLinks($data, $format, $context));
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof LinkCollection;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            LinkCollection::class => true,
        ];
    }

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function normalizeLinks(LinkCollection $linkCollection, ?string $format, array $context): array
    {
        $normalized = [];
        foreach ($linkCollection->jsonSerialize() as $key => $link) {
            $normalized[$key] = $this->normalizer->normalize($link, $format, $context);
        }

        return $normalized;
    }
}
