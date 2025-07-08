<?php

declare(strict_types=1);

namespace App\Domain\Upload\WooDecision;

use App\Domain\Upload\Dossier\DossierUploadRequestValidator;
use App\Domain\Upload\UploadRequest;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentUploadVoter extends Voter
{
    public function __construct(
        private readonly DossierUploadRequestValidator $requestValidator,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->requestValidator->supports(
            $attribute,
            $subject,
            UploadGroupId::WOO_DECISION_DOCUMENTS,
        );
    }

    /**
     * @param UploadRequest $subject
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->requestValidator->isValidUploadRequest($subject);
    }
}
