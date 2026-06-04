<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Shared\ValueObject\PlainDate;

class PlainDateAfterOrEqualValidator extends PlainDateValidator
{
    protected function validatePlainDate(PlainDate $value, PlainDate $comparedValue, string $message): void
    {
        if ($value->isBefore($comparedValue)) {
            $this->context->buildViolation($message)
                ->setParameter('{{ limit }}', $comparedValue->format('Y-m-d'))
                ->setCode(PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR)
                ->addViolation();
        }
    }
}
