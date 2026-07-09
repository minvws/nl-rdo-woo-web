<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use PublicationApi\Api\Pagination\CursorPage;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

use function array_map;

#[AutoconfigureTag('serializer.normalizer')]
final class CursorPageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        Assert::isInstanceOf($object, CursorPage::class);

        $result = [
            'items' => array_map(
                fn (mixed $item): mixed => $this->normalizer->normalize($item, $format, $context),
                $object->items,
            ),
            'hasNextPage' => $object->hasNextPage,
        ];

        if ($object->halLinks !== null) {
            $result['_links'] = $this->normalizer->normalize($object->halLinks, $format, $context);
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CursorPage;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [CursorPage::class => true];
    }
}
