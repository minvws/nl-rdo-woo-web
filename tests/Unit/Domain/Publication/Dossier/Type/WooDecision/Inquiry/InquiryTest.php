<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

final class InquiryTest extends UnitTestCase
{
    public function testDefaultId(): void
    {
        $inquiry = new Inquiry();

        self::assertInstanceOf(WooUuid::class, $inquiry->getId());
    }

    public function testGivenId(): void
    {
        $id = Uuid::fromString($this->getFaker()->uuid());
        $inquiry = new Inquiry($id);

        self::assertSame($id, $inquiry->getId());
    }

    public function testAddAndRemoveDossier(): void
    {
        $inquiry = new Inquiry();

        $wooDecision = Mockery::mock(WooDecision::class);
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
        $wooDecisionScheduled = Mockery::mock(WooDecision::class);
        $wooDecisionScheduled->expects('getStatus')->andReturn(DossierStatus::SCHEDULED);

        $wooDecisionConcept = Mockery::mock(WooDecision::class);
        $wooDecisionConcept->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $inquiry = new Inquiry();
        $inquiry->addDossier($wooDecisionConcept);
        $inquiry->addDossier($wooDecisionScheduled);

        self::assertEquals(
            [$wooDecisionScheduled],
            $inquiry->getScheduledDossiers()->getValues(),
        );
    }
}
