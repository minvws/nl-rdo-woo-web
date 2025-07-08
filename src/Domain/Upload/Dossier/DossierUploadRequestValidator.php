<?php

declare(strict_types=1);

namespace App\Domain\Upload\Dossier;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Upload\UploadRequest;
use App\Domain\Upload\UploadService;
use App\Service\Uploader\UploadGroupId;
use Symfony\Bundle\SecurityBundle\Security;

class DossierUploadRequestValidator
{
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly Security $security,
    ) {
    }

    public function supports(string $attribute, mixed $subject, UploadGroupId $allowedUploadGroup): bool
    {
        if ($attribute !== UploadService::SECURITY_ATTRIBUTE || ! $subject instanceof UploadRequest) {
            return false;
        }

        return $subject->groupId === $allowedUploadGroup
            && $subject->additionalParameters->has('dossierId');
    }

    public function isValidUploadRequest(UploadRequest $subject): bool
    {
        $dossier = $this->repository->find(
            $subject->additionalParameters->getString('dossierId')
        );

        if ($dossier === null) {
            return false;
        }

        return $this->security->isGranted(
            $dossier->getStatus()->isConcept() ? 'AuthMatrix.dossier.create' : 'AuthMatrix.dossier.update',
            $dossier,
        );
    }
}
