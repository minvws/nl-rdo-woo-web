<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator\Violation;

use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ConstraintViolationBuilderTest extends UnitTestCase
{
    public function testPrefixPropertyPathsPrefixesCorrectly(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('fileName', 'must not be blank'),
            $this->createViolation('formalDate', 'invalid date'),
        ]);

        $result = ConstraintViolationBuilder::prefixPropertyPaths($violations, 'attachments.');

        self::assertCount(2, $result);
        self::assertSame('attachments.fileName', $result->get(0)->getPropertyPath());
        self::assertSame('attachments.formalDate', $result->get(1)->getPropertyPath());
    }

    public function testPrefixPropertyPathsPreservesViolationFields(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('title', 'too short', 'abc', 'length_error'),
        ]);

        $result = ConstraintViolationBuilder::prefixPropertyPaths($violations, 'doc.');

        self::assertCount(1, $result);

        $violation = $result->get(0);
        self::assertSame('doc.title', $violation->getPropertyPath());
        self::assertSame('too short', $violation->getMessage());
        self::assertSame('abc', $violation->getInvalidValue());
        self::assertSame('length_error', $violation->getCode());
    }

    public function testPrefixPropertyPathsWithEmptyList(): void
    {
        $result = ConstraintViolationBuilder::prefixPropertyPaths(new ConstraintViolationList(), 'prefix.');

        self::assertCount(0, $result);
    }

    private function createViolation(
        string $propertyPath,
        string $message,
        mixed $invalidValue = null,
        ?string $code = null,
    ): ConstraintViolation {
        return new ConstraintViolation(
            message: $message,
            messageTemplate: null,
            parameters: [],
            root: null,
            propertyPath: $propertyPath,
            invalidValue: $invalidValue,
            plural: null,
            code: $code,
        );
    }
}
