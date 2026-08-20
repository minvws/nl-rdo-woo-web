<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\DraftDecision;

use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DraftDecisionMainDocumentTest extends UnitTestCase
{
    public function testAllowedTypes(): void
    {
        self::assertSame(
            [AttachmentType::LEGISLATIVE_PROPOSAL],
            DraftDecisionMainDocument::getAllowedTypes(),
        );
    }
}
