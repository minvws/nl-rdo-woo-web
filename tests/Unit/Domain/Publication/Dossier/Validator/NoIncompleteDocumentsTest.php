<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Shared\Domain\Publication\Dossier\Validator\NoIncompleteDocuments;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;

class NoIncompleteDocumentsTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $constraint = new NoIncompleteDocuments();

        self::assertSame('dossier.incomplete_documents', $constraint->message);
    }

    public function testIsClassConstraint(): void
    {
        $constraint = new NoIncompleteDocuments();

        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
