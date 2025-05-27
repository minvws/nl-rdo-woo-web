<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DocumentVoter extends WooDecisionVoter
{
    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === DossierVoter::VIEW
            && $subject instanceof Document
            && $subject->getDossiers()->count() === 1;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
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
