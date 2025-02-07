<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Security\DossierVoter;
use App\Service\Security\WooDecisionVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionVoterTest extends MockeryTestCase
{
    private WooDecisionVoter $voter;

    private InquirySessionService&MockInterface $inquirySessionService;

    public function setUp(): void
    {
        $this->inquirySessionService = \Mockery::mock(InquirySessionService::class);

        $this->voter = new WooDecisionVoter(
            $this->inquirySessionService,
        );

        parent::setUp();
    }

    public function testAbstainForUnknownAttribute(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new WooDecision(), ['foo']),
        );
    }

    public function testAbstainForCovenant(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new Covenant(), [DossierVoter::VIEW]),
        );
    }

    public function testAbstainForUnknownSubject(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new \stdClass(), [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedForPublishedDossier(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsNotPublishedAndNotPreview(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsPreviewAndNotInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getId')->andReturn(Uuid::v6());

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getId')->andReturn(Uuid::v6());

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar']);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDossierIsPreviewAndInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getId')->andReturn(Uuid::v6());

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getId')->andReturn($inquiryIdB = Uuid::v6());

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar', $inquiryIdB]);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }
}
