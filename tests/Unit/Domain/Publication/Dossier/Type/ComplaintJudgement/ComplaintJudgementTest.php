<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Carbon\CarbonImmutable;
use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\DossierType;

final class ComplaintJudgementTest extends TestCase
{
    public function testGetType(): void
    {
        $dossier = new ComplaintJudgement();
        self::assertEquals(DossierType::COMPLAINT_JUDGEMENT, $dossier->getType());
    }

    public function testGetAndSetMainDocument(): void
    {
        $dossier = new ComplaintJudgement();
        self::assertNull($dossier->getMainDocument());

        $document = Mockery::mock(ComplaintJudgementMainDocument::class);
        $dossier->setMainDocument($document);

        self::assertEquals($document, $dossier->getMainDocument());
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
