<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class ComplaintJudgementTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new ComplaintJudgement();
        self::assertEquals(DossierType::COMPLAINT_JUDGEMENT, $dossier->getType());
    }

    public function testGetAndSetDocument(): void
    {
        $dossier = new ComplaintJudgement();
        self::assertNull($dossier->getDocument());

        $document = \Mockery::mock(ComplaintJudgementDocument::class);
        $dossier->setDocument($document);

        self::assertEquals($document, $dossier->getDocument());
    }

    public function testSetDateFromSetsDateTo(): void
    {
        $dossier = new ComplaintJudgement();

        $date = new CarbonImmutable();

        $dossier->setDateFrom($date);

        self::assertEquals($date, $dossier->getDateFrom());
        self::assertEquals($date, $dossier->getDateTo());
    }
}
