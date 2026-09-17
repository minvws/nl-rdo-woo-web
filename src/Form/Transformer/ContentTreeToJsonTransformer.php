<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use InvalidArgumentException;
use JsonException;
use Shared\Domain\Publication\Subject\SubjectContentTree;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function array_is_list;
use function get_debug_type;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ContentTreeToJsonTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof SubjectContentTree) {
            throw new TransformationFailedException(
                sprintf('Expected %s, got %s', SubjectContentTree::class, get_debug_type($value)),
            );
        }

        if ($value->children === [] && $value->title === '' && $value->intro === '' && $value->outro === '') {
            return '';
        }

        try {
            return json_encode(
                $value->toArray(),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $jsonException) {
            throw new TransformationFailedException($jsonException->getMessage(), previous: $jsonException);
        }
    }

    public function reverseTransform(mixed $value): ?SubjectContentTree
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected string, got %s', get_debug_type($value)),
            );
        }

        if (trim($value) === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new TransformationFailedException(
                message: $jsonException->getMessage(),
                invalidMessage: 'subject_landing_page_content_tree_invalid_json',
                previous: $jsonException,
            );
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new TransformationFailedException(
                message: 'Expected a content tree object',
                invalidMessage: 'subject_landing_page_content_tree_invalid_structure',
            );
        }

        try {
            return SubjectContentTree::fromArray($decoded);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new TransformationFailedException(
                message: 'Expected a list of content nodes',
                invalidMessage: 'subject_landing_page_content_tree_invalid_structure',
                previous: $invalidArgumentException,
            );
        }
    }
}
