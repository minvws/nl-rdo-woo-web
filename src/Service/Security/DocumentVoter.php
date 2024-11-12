<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DocumentVoter extends WooDecisionVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === DossierVoter::VIEW
            && $subject instanceof Document
            && $subject->getDossiers()->count() === 1;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $document = $subject;
        if (! $document instanceof Document) {
            return false;
        }

        /** @var WooDecision $dossier */
        $dossier = $document->getDossiers()->first();
        if (parent::voteOnAttribute($attribute, $dossier, $token) === true) {
            return true;
        }

        // Check all inquiry ids from the document to see if we have one matching in our session.
        $inquiryIds = $this->inquirySession->getInquiries();
        foreach ($document->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        return false;
    }
}
