<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Override;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

use function in_array;

class DocumentVoter extends WooDecisionVoter
{
    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === DossierVoter::VIEW && $subject instanceof Document;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $document = $subject;
        if (! $document instanceof Document) {
            return false;
        }

        $wooDecisions = $document->getDossiers();
        if ($wooDecisions->isEmpty()) {
            return false;
        }

        foreach ($wooDecisions as $wooDecision) {
            if (parent::voteOnAttribute($attribute, $wooDecision, $token) === true) {
                return true;
            }
        }

        return $this->checkForDocumentInquiryIdInSession($document);
    }

    private function checkForDocumentInquiryIdInSession(Document $document): bool
    {
        $inquiryIds = $this->inquirySession->getInquiries();
        foreach ($document->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                return true;
            }
        }

        return false;
    }
}
