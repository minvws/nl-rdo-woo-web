<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\DraftDecision;

use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\ValueObject\PlainDate;

final class DraftDecisionTest extends TestCase
{
    public function testGetType(): void
    {
        self::assertSame(DossierType::DRAFT_DECISION, new DraftDecision()->getType());
    }

    public function testGetMainDocumentEntityClass(): void
    {
        self::assertSame(DraftDecisionMainDocument::class, new DraftDecision()->getMainDocumentEntityClass());
    }

    public function testGetAttachmentEntityClass(): void
    {
        self::assertSame(DraftDecisionAttachment::class, new DraftDecision()->getAttachmentEntityClass());
    }

    public function testSetDateFromSetsDateTo(): void
    {
        $dossier = new DraftDecision();

        $date = PlainDate::today();

        $dossier->setDateFrom($date);

        self::assertEquals($date, $dossier->getDateFrom());
        self::assertEquals($date, $dossier->getDateTo());
    }
}
