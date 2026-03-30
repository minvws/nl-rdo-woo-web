<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Shared\Domain\Publication\Dossier\Validator\NoIncompleteAttachments;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;

class NoIncompleteAttachmentsTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $constraint = new NoIncompleteAttachments();

        self::assertSame('dossier.incomplete_attachments', $constraint->message);
    }

    public function testIsClassConstraint(): void
    {
        $constraint = new NoIncompleteAttachments();

        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
