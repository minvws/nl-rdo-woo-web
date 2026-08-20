<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\FeatureFlag;

use Mockery;
use PublicationApi\FeatureFlag\DraftDecisionFeatureVoter;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class DraftDecisionFeatureVoterTest extends UnitTestCase
{
    public function testAbstainsForUnknownAttribute(): void
    {
        $voter = new DraftDecisionFeatureVoter(true);

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(Mockery::mock(TokenInterface::class), null, ['UnknownFeature']),
        );
    }

    public function testGrantsAccessWhenFeatureIsEnabled(): void
    {
        $voter = new DraftDecisionFeatureVoter(true);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                Mockery::mock(TokenInterface::class),
                null,
                [DraftDecisionFeatureVoter::ATTRIBUTE],
            ),
        );
    }

    public function testDeniesAccessWhenFeatureIsDisabled(): void
    {
        $voter = new DraftDecisionFeatureVoter(false);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                Mockery::mock(TokenInterface::class),
                null,
                [DraftDecisionFeatureVoter::ATTRIBUTE],
            ),
        );
    }
}
