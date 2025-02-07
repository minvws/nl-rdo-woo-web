<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Tests\Unit\UnitTestCase;

final class CovenantMainDocumentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        $this->assertMatchesJsonSnapshot(CovenantMainDocument::getAllowedTypes());
    }
}
