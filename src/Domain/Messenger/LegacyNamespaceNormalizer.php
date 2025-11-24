<?php

declare(strict_types=1);

namespace Shared\Domain\Messenger;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Webmozart\Assert\Assert;

final class LegacyNamespaceNormalizer implements DenormalizerInterface, SerializerAwareInterface
{
    private ?DenormalizerInterface $normalizer = null;

    public function setSerializer(SerializerInterface $serializer): void
    {
        Assert::isInstanceOf($serializer, DenormalizerInterface::class);
        /** @var SerializerInterface&DenormalizerInterface $serializer */
        $this->normalizer = $serializer;
    }

    public function getSerializer(): DenormalizerInterface
    {
        Assert::notNull($this->normalizer);

        return $this->normalizer;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $newType = $this->convertLegacyNamespace($type);

        if ($newType !== $type && class_exists($newType)) {
            $type = $newType;
        }

        if (! isset($context['legacy_namespace_normalized'])) {
            $context['legacy_namespace_normalized'] = [];
        }

        /** @var array{'legacy_namespace_normalized':array<class-name,true>} $context */
        $context['legacy_namespace_normalized'][$type] = true;

        return $this->getSerializer()->denormalize($data, $type, $format, $context);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        unset($data, $format);

        /** @var array{'legacy_namespace_normalized'?:array<class-name,true>} $context */
        if (isset($context['legacy_namespace_normalized'][$type])) {
            return false;
        }

        if (str_starts_with($type, 'App\\')) {
            $newType = $this->convertLegacyNamespace($type);

            return $newType !== $type && class_exists($newType);
        }

        return false;
    }

    /**
     * @return array<class-string|'*'|'object'|string,bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        unset($format);

        return [
            'object' => null, // null means: always call supportsDenormalization() to check
        ];
    }

    private function convertLegacyNamespace(string $type): string
    {
        if (str_starts_with($type, 'App\\')) {
            return 'Shared\\' . substr($type, 4);
        }

        return $type;
    }
}
