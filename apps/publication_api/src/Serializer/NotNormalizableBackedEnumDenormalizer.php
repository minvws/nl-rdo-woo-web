<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use Shared\Serializer\PathFromContext;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer as BackedEnumDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[AsDecorator('serializer.normalizer.backed_enum')]
readonly class NotNormalizableBackedEnumDenormalizer implements DenormalizerInterface
{
    use PathFromContext;

    public function __construct(
        #[AutowireDecorated]
        private BackedEnumDenormalizer $backedEnumDenormalizer,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        try {
            return $this->backedEnumDenormalizer->denormalize($data, $type, $format, $context);
        } catch (NotNormalizableValueException $exception) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'Value not allowed',
                $data,
                [],
                $this->getPathFromContext($context),
                true,
                $exception->getCode(),
                $exception,
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->backedEnumDenormalizer->supportsDenormalization($data, $type, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->backedEnumDenormalizer->getSupportedTypes($format);
    }
}
