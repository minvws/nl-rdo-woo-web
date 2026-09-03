<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Serializer\PathFromContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

use function array_is_list;
use function array_key_exists;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

#[AutoconfigureTag('serializer.normalizer', ['priority' => -880])]
final class SubjectContentNodeDenormalizer implements DenormalizerInterface
{
    use PathFromContext;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): SubjectContentNode
    {
        $path = $this->getPathFromContext($context);

        if (! is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('Expected array for SubjectContentNode, got "%s".', get_debug_type($data)),
                $data,
                ['array'],
                $path,
                true,
            );
        }

        if (! array_key_exists('title', $data) || ! is_string($data['title'])) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'SubjectContentNode requires a string "title" field.',
                $data['title'] ?? null,
                ['string'],
                $path !== null ? $path . '.title' : 'title',
                true,
            );
        }

        if (! array_key_exists('body', $data) || ! is_string($data['body'])) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'SubjectContentNode requires a string "body" field.',
                $data['body'] ?? null,
                ['string'],
                $path !== null ? $path . '.body' : 'body',
                true,
            );
        }

        if (! array_key_exists('children', $data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'SubjectContentNode requires a "children" field.',
                null,
                ['array'],
                $path !== null ? $path . '.children' : 'children',
                true,
            );
        }

        $rawChildren = $data['children'];
        if (! is_array($rawChildren) || ! array_is_list($rawChildren)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                'SubjectContentNode "children" must be a list.',
                $rawChildren,
                ['array'],
                $path !== null ? $path . '.children' : 'children',
                true,
            );
        }

        $children = [];
        foreach ($rawChildren as $i => $child) {
            $childPath = ($path !== null ? $path . '.children' : 'children') . '[' . $i . ']';
            $children[] = $this->denormalize($child, SubjectContentNode::class, $format, ['deserialization_path' => $childPath] + $context);
        }

        return new SubjectContentNode($data['title'], $data['body'], $children);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === SubjectContentNode::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [SubjectContentNode::class => true];
    }
}
