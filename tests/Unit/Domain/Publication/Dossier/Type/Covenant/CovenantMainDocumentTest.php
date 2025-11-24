<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class CovenantMainDocumentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        $this->assertMatchesJsonSnapshot(CovenantMainDocument::getAllowedTypes());
    }
}
