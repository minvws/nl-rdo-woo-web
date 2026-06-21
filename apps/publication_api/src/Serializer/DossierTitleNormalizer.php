<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use InvalidArgumentException;
use Shared\Domain\Exception\DossierTitleArgumentException;
use Shared\Serializer\PathFromContext;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Webmozart\Assert\Assert;

#[AutoconfigureTag('serializer.normalizer')]
final class DossierTitleNormalizer implements NormalizerInterface, DenormalizerInterface
{
    use PathFromContext;

    public function normalize(mixed $data, ?string $format = null, array $context = []): string
    {
        Assert::isInstanceOf($data, DossierTitle::class);

        return $data->toString();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DossierTitle
    {
        try {
            Assert::string($data);
        } catch (InvalidArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'The data is either not a string or null (if allowed)',
                $data,
                [TypeIdentifier::STRING->value],
                $this->getPathFromContext($context),
                true,
            );
        }

        try {
            return DossierTitle::create($data);
        } catch (DossierTitleArgumentException $dossierTitleArgumentException) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $dossierTitleArgumentException->getTranslationKey(),
                $data,
                [],
                $this->getPathFromContext($context),
                true,
                0,
                $dossierTitleArgumentException,
            );
        }
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DossierTitle;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === DossierTitle::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DossierTitle::class => true,
        ];
    }
}
