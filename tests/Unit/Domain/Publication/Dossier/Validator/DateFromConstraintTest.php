<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Validator;

use App\Domain\Publication\Dossier\Validator\DateFromConstraint;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DateFromConstraintTest extends MockeryTestCase
{
    public function testConstructor(): void
    {
        $constraint = new DateFromConstraint($mode = 'foo', $message = 'bar');

        self::assertEquals($mode, $constraint->mode);
        self::assertEquals($message, $constraint->message);
    }
}
