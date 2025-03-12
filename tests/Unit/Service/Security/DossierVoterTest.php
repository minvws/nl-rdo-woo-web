<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\Security\DossierVoter;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DossierVoterTest extends MockeryTestCase
{
    private DossierVoter $voter;

    public function setUp(): void
    {
        $this->voter = new DossierVoter();

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

    public function testAbstainForWooDecision(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new WooDecision(), [DossierVoter::VIEW]),
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

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        self::assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }

    public function testAccessDeniedForUnPublishedDossier(): void
    {
        $token = \Mockery::mock(TokenInterface::class);

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $dossier, [DossierVoter::VIEW]),
        );
    }
}
