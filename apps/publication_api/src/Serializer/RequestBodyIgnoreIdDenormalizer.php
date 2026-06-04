<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_array;

/**
 * API Platform's ItemNormalizer has hardcoded handling for an `id` field in the request body:
 * when present, it tries to resolve it as an IRI to find an existing resource to populate.
 * If the value is not a valid IRI (e.g. a plain UUID), it throws an InvalidArgumentException,
 * resulting in a 500 response. This denormalizer runs before ItemNormalizer and strips `id`
 * from the input data for any class that does not declare `id` as a property.
 *
 * To opt out (i.e. keep `id` in the body), add to the operation's denormalization context:
 * `denormalizationContext: ['preserve_id_in_body' => true]`
 *
 * @see \ApiPlatform\Serializer\ItemNormalizer::denormalize()
 */
#[AutoconfigureTag('serializer.normalizer', ['priority' => -890])]
final class RequestBodyIgnoreIdDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        Assert::isArray($data);
        unset($data['id']);

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (array_key_exists('preserve_id_in_body', $context) && $context['preserve_id_in_body'] === true) {
            return false;
        }

        if (! is_array($data)) {
            return false;
        }

        if (! array_key_exists('id', $data)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
