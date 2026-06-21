<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Override;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Webmozart\Assert\Assert;

use function in_array;

class DocumentVoter extends WooDecisionVoter
{
    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === DossierVoter::VIEW
            && $subject instanceof Document
            && $subject->getDossiers()->count() === 1;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $document = $subject;
        if (! $document instanceof Document) {
            return false;
        }

        $dossier = $document->getDossiers()->first();
        Assert::isInstanceOf($dossier, WooDecision::class);

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
