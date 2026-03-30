<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer as BackedEnumDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

use function array_key_exists;
use function is_string;

#[AsDecorator('serializer.normalizer.backed_enum')]
readonly class NotNormalizableBackedEnumDenormalizer implements DenormalizerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private BackedEnumDenormalizer $backedEnumDenormalizer, // the symfony service that implements the DenormalizerInterface
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        try {
            /** @throws InvalidArgumentException */
            return $this->backedEnumDenormalizer->denormalize($data, $type, $format, $context);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $invalidArgumentException->getMessage(),
                $data,
                [TypeIdentifier::STRING->value],
                $this->getPathFromContext($context),
                true,
                0,
                $invalidArgumentException,
            );
        }
    }

    /**
     * @param array<mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->backedEnumDenormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->backedEnumDenormalizer->getSupportedTypes($format);
    }

    /**
     * @param array<mixed> $context
     */
    private function getPathFromContext(array $context): ?string
    {
        if (! array_key_exists('deserialization_path', $context)) {
            return null;
        }

        $deserializationPath = $context['deserialization_path'];
        if (! is_string($deserializationPath)) {
            return null;
        }

        return $deserializationPath;
    }
}
