<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\Inquiry\InquirySessionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WooDecisionVoter extends Voter
{
    public function __construct(
        protected readonly InquirySessionService $inquirySession,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === DossierVoter::VIEW && $subject instanceof WooDecision;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (! $subject instanceof WooDecision) {
            return false;
        }

        // If dossier is published, allow viewing
        if ($subject->getStatus()->isPublished()) {
            return true;
        }

        // If dossier is not preview, deny access
        if (! $subject->getStatus()->isPreview()) {
            return false;
        }

        $inquiryIds = $this->inquirySession->getInquiries();

        // Check if any inquiry id from the dossier is in the session inquiry ids.
        foreach ($subject->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        return false;
    }
}
