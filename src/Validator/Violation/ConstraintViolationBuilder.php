<?php

declare(strict_types=1);

namespace Shared\Validator\Violation;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

use function sprintf;

class ConstraintViolationBuilder
{
    public const string ENTITY_MISSING_ERROR = '1f0c13f5-7135-670a-8c9b-c9120dd3a68b';

    public static function createList(ConstraintViolation ...$violations): ConstraintViolationList
    {
        return new ConstraintViolationList($violations);
    }

    public static function forMissingEntity(string $entityName, string $propertyPath): ConstraintViolation
    {
        return new ConstraintViolation(
            sprintf('The referenced %s could not be found', $entityName),
            null,
            [],
            null,
            $propertyPath,
            '',
            null,
            self::ENTITY_MISSING_ERROR,
        );
    }

    public static function forViolation(ConstraintViolationInterface $violation, string $propertyPath): ConstraintViolation
    {
        return new ConstraintViolation(
            $violation->getMessage(),
            $violation->getMessageTemplate(),
            $violation->getParameters(),
            $violation->getRoot(),
            $propertyPath,
            $violation->getInvalidValue(),
            $violation->getPlural(),
            $violation->getCode(),
            $violation->getConstraint(),
            $violation->getCause(),
        );
    }
}
