<?php

declare(strict_types=1);

namespace App\Domain\Upload\Department;

use App\Domain\Department\DepartmentRepository;
use App\Domain\Department\DepartmentService;
use App\Domain\Upload\UploadRequest;
use App\Domain\Upload\UploadService;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class DepartmentUploadVoter extends Voter
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly DepartmentService $departmentService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== UploadService::SECURITY_ATTRIBUTE || ! $subject instanceof UploadRequest) {
            return false;
        }

        return $subject->groupId === UploadGroupId::DEPARTMENT
            && $subject->additionalParameters->has('departmentId');
    }

    /**
     * @param UploadRequest $subject
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $department = $this->repository->find(
            $subject->additionalParameters->getString('departmentId')
        );

        if ($department === null) {
            return false;
        }

        return $this->departmentService->userCanEditLandingpage($department);
    }
}
