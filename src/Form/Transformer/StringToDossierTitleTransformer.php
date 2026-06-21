<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use Shared\Domain\Exception\DossierTitleArgumentException;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function get_debug_type;
use function gettype;
use function is_string;
use function sprintf;

class StringToDossierTitleTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof DossierTitle) {
            $type = get_debug_type($value);

            throw new TransformationFailedException(
                sprintf('Expected DossierTitle, got %s', $type),
            );
        }

        return $value->toString();
    }

    public function reverseTransform(mixed $value): DossierTitle
    {
        if ($value === '' || $value === null) {
            throw new TransformationFailedException(
                message: 'dossier.title_required',
                invalidMessage: 'dossier.title_required',
            );
        }

        if (! is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected string, got %s', gettype($value)),
            );
        }

        try {
            return DossierTitle::create($value);
        } catch (DossierTitleArgumentException $dossierTitleArgumentException) {
            throw new TransformationFailedException(
                message: $dossierTitleArgumentException->getMessage(),
                invalidMessage: $dossierTitleArgumentException->getTranslationKey(),
                invalidMessageParameters: $dossierTitleArgumentException->getParameters(),
            );
        }
    }
}
