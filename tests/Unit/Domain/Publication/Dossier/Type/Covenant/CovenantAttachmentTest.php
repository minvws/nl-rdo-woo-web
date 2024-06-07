<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Tests\Unit\UnitTestCase;

final class CovenantAttachmentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        $this->assertMatchesJsonSnapshot(CovenantAttachment::getAllowedTypes());
    }
}
