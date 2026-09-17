<?php

declare(strict_types=1);

namespace Shared\Serializer;

use InvalidArgumentException;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class DocumentNumberNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, DocumentNumber::class);

        return $data->toString();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DocumentNumber;
    }

    /**
     * @return array<class-string<mixed>|'*'|'object'|string,bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            DocumentNumber::class => true,
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if null is allowed)',
                $data,
                [],
                $this->getPathFromContext($context),
                true,
            );
        }

        return DocumentNumber::fromString($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === DocumentNumber::class;
    }
}
