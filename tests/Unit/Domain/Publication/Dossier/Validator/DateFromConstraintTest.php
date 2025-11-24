<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Shared\Domain\Publication\Dossier\Validator\DateFromConstraint;
use Shared\Tests\Unit\UnitTestCase;

class DateFromConstraintTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $constraint = new DateFromConstraint($mode = 'foo', $message = 'bar');

        self::assertEquals($mode, $constraint->mode);
        self::assertEquals($message, $constraint->message);
    }
}
