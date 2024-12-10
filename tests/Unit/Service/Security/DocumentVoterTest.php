<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Security\DocumentVoter;
use App\Service\Security\DossierVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

class DocumentVoterTest extends MockeryTestCase
{
    private DocumentVoter $voter;

    private InquirySessionService&MockInterface $inquirySessionService;

    public function setUp(): void
    {
        $this->inquirySessionService = \Mockery::mock(InquirySessionService::class);

        $this->voter = new DocumentVoter(
            $this->inquirySessionService,
        );

        parent::setUp();
    }

    public function testAbstainForUnknownAttribute(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $dossier = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $document, ['foo']),
        );
    }

    public function testAbstainWhenDocumentHasMultipleDossiers(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierB = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossierA,
            $dossierB,
        ]));

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $document, ['foo']),
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

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsNotPublishedAndNotPreviewAndDocumentNotInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection());

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar']);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedWhenDossierIsPreviewAndNotInSessionAndDocumentNotInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getId')->andReturn(Uuid::v6());

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getId')->andReturn(Uuid::v6());

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar']);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDossierIsPreviewAndInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getId')->andReturn(Uuid::v6());

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getId')->andReturn($inquiryBId = Uuid::v6());

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar', $inquiryBId]);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }

    public function testAccessGrantedWhenDocumentInSession(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $inquiryA = \Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getId')->andReturn(Uuid::v6());

        $inquiryB = \Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getId')->andReturn($inquiryBId = Uuid::v6());

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PREVIEW);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection());

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([
            $dossier,
        ]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([
            $inquiryA,
            $inquiryB,
        ]));

        $this->inquirySessionService->shouldReceive('getInquiries')->andReturn(['foo', 'bar', $inquiryBId]);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $document, [DossierVoter::VIEW]),
        );
    }
}
