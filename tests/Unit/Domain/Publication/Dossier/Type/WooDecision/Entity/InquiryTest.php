<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use PHPUnit\Framework\TestCase;

final class InquiryTest extends TestCase
{
    public function testGetBatchFileName(): void
    {
        $inquiry = new Inquiry();
        $inquiry->setCasenr($caseNr = 'tst-123');

        self::assertEquals(
            $caseNr,
            $inquiry->getBatchFileName(),
        );
    }

    public function testAddAndRemoveDossier(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);

        $inquiry = new Inquiry();
        $inquiry->addDossier($wooDecision);

        self::assertEquals(
            [$wooDecision],
            $inquiry->getDossiers()->toArray(),
        );

        $inquiry->removeDossier($wooDecision);
        self::assertTrue($inquiry->getDossiers()->isEmpty());
    }

    public function testGetPubliclyAvailableDossiers(): void
    {
        $wooDecisionPublished = \Mockery::mock(WooDecision::class);
        $wooDecisionPublished->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $wooDecisionConcept = \Mockery::mock(WooDecision::class);
        $wooDecisionConcept->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $inquiry = new Inquiry();
        $inquiry->addDossier($wooDecisionConcept);
        $inquiry->addDossier($wooDecisionPublished);

        self::assertEqualsCanonicalizing(
            [$wooDecisionPublished],
            $inquiry->getPubliclyAvailableDossiers()->toArray(),
        );
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
