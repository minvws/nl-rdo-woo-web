<?php

declare(strict_types=1);

namespace Shared\Validator\Violation;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

use function sprintf;

class ConstraintViolationBuilder
{
    public const string ENTITY_MISSING_ERROR = '1f0c13f5-7135-670a-8c9b-c9120dd3a68b';
    public const string MODIFIED_SUB_ENTITY_ERROR = '1f2a4c8e-3b91-6d72-9f04-7e5a1c8b2d63';

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

    public static function forModifiedSubEntity(string $propertyPath): ConstraintViolation
    {
        return new ConstraintViolation(
            sprintf('The "%s" cannot be modified', $propertyPath),
            null,
            [],
            null,
            $propertyPath,
            null,
            null,
            self::MODIFIED_SUB_ENTITY_ERROR,
        );
    }

    public static function prefixPropertyPaths(ConstraintViolationListInterface $violations, string $prefix): ConstraintViolationList
    {
        $constraintViolationList = self::createList();

        foreach ($violations as $violation) {
            $constraintViolationList->add(self::forViolation($violation, sprintf('%s%s', $prefix, $violation->getPropertyPath())));
        }

        return $constraintViolationList;
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
