<?php

declare(strict_types=1);

namespace PublicationApi\Serializer;

use ApiPlatform\Serializer\AbstractConstraintViolationListNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

use function array_key_exists;
use function array_map;

/**
 * Converts {@see ConstraintViolationListInterface} to the API Problem spec (RFC 7807).
 *
 * Overrides the default API Platform normalizer to ensure violation codes are always strings,
 * never null, to comply with the JSON schema.
 *
 * Note that this might be addressed in future versions of API Platform.
 *
 * @see https://tools.ietf.org/html/rfc7807
 */
#[AsDecorator('api_platform.normalizer.constraint_violation_list')]
final class ConstraintViolationListNormalizer extends AbstractConstraintViolationListNormalizer
{
    public const FORMAT = 'json';

    /**
     * @param ?array<array-key,mixed> $serializePayloadFields
     */
    public function __construct(?array $serializePayloadFields = null, ?NameConverterInterface $nameConverter = null)
    {
        parent::__construct($serializePayloadFields, $nameConverter);
    }

    /**
     * @param ConstraintViolationListInterface $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /**
         * @var array<string,string> $messages
         * @var array<array-key,array<string,mixed>> $violations
         */
        [$messages, $violations] = parent::getMessagesAndViolations($object);
        unset($messages);

        // Ensure all violation codes are strings, never null
        $violations = array_map(function (array $violation): array {
            if (array_key_exists('code', $violation) && $violation['code'] === null) {
                $violation['code'] = '';
            }

            return $violation;
        }, $violations);

        return $violations;
    }
}
