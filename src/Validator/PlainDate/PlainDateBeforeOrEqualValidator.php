<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Shared\ValueObject\PlainDate;

class PlainDateBeforeOrEqualValidator extends PlainDateValidator
{
    protected function validatePlainDate(PlainDate $value, PlainDate $comparedValue, string $message): void
    {
        if ($value->isAfter($comparedValue)) {
            $this->context->buildViolation($message)
                ->setParameter('{{ limit }}', $comparedValue->format('Y-m-d'))
                ->setCode(PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR)
                ->addViolation();
        }
    }
}
