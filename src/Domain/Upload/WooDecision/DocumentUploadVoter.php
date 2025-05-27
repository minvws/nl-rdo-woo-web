<?php

declare(strict_types=1);

namespace App\Domain\Upload\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Uploader\UploadRequest;
use App\Domain\Uploader\UploadService;
use App\Service\Uploader\UploadGroupId;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentUploadVoter extends Voter
{
    public function __construct(
        private readonly WooDecisionRepository $repository,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== UploadService::SECURITY_ATTRIBUTE || ! $subject instanceof UploadRequest) {
            return false;
        }

        return $subject->groupId === UploadGroupId::WOO_DECISION_DOCUMENTS
            && $subject->additionalParameters->has('dossierId');
    }

    /**
     * @param UploadRequest $subject
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $wooDecision = $this->repository->find(
            $subject->additionalParameters->getString('dossierId')
        );

        if ($wooDecision === null) {
            return false;
        }

        return $this->security->isGranted(
            $wooDecision->getStatus()->isConcept() ? 'AuthMatrix.dossier.create' : 'AuthMatrix.dossier.update',
            $wooDecision,
        );
    }
}
