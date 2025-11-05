<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use PHPUnit\Framework\TestCase;

final class InquiryTest extends TestCase
{
    public function testAddAndRemoveDossier(): void
    {
        $inquiry = new Inquiry();

        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->expects('removeInquiry')->with($inquiry);

        $inquiry->addDossier($wooDecision);

        self::assertEquals(
            [$wooDecision],
            $inquiry->getDossiers()->toArray(),
        );

        $inquiry->removeDossier($wooDecision);
        self::assertTrue($inquiry->getDossiers()->isEmpty());
    }

    public function testGetScheduledDossiers(): void
    {
        $wooDecisionScheduled = \Mockery::mock(WooDecision::class);
        $wooDecisionScheduled->expects('getStatus')->andReturn(DossierStatus::SCHEDULED);

        $wooDecisionConcept = \Mockery::mock(WooDecision::class);
        $wooDecisionConcept->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $inquiry = new Inquiry();
        $inquiry->addDossier($wooDecisionConcept);
        $inquiry->addDossier($wooDecisionScheduled);

        self::assertEqualsCanonicalizing(
            [$wooDecisionScheduled],
            $inquiry->getScheduledDossiers()->toArray(),
        );
    }
}
