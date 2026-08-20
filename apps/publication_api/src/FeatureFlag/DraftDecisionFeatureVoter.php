<?php

declare(strict_types=1);

namespace PublicationApi\FeatureFlag;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DraftDecisionFeatureVoter extends Voter
{
    public const string ATTRIBUTE = 'DraftDecisionFeature';

    public function __construct(
        #[Autowire(env: 'bool:HAS_FEATURE_DRAFT_DECISION')]
        private readonly bool $hasFeatureDraftDecision,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        unset($subject);

        return $attribute === self::ATTRIBUTE;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        unset($attribute, $subject, $token, $vote);

        return $this->hasFeatureDraftDecision;
    }
}
