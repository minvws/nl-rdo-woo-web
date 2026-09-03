<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use InvalidArgumentException;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function get_debug_type;
use function is_string;
use function sprintf;

class StringToLandingPageTitleTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof LandingPageTitle) {
            throw new TransformationFailedException(
                sprintf('Expected LandingPageTitle, got %s', get_debug_type($value)),
            );
        }

        return $value->toString();
    }

    public function reverseTransform(mixed $value): LandingPageTitle
    {
        if ($value === '' || $value === null) {
            throw new TransformationFailedException(
                message: 'Landing page title cannot be empty',
                invalidMessage: 'subject_landing_page_title_required',
            );
        }

        if (! is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected string, got %s', get_debug_type($value)),
            );
        }

        try {
            return LandingPageTitle::create($value);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new TransformationFailedException(
                message: $invalidArgumentException->getMessage(),
                invalidMessage: $invalidArgumentException->getCode() === LandingPageTitle::ERROR_EMPTY
                    ? 'subject_landing_page_title_required'
                    : 'subject_landing_page_title_invalid_length',
                invalidMessageParameters: [
                    '{{ max }}' => LandingPageTitle::MAX_LENGTH,
                ],
            );
        }
    }
}
