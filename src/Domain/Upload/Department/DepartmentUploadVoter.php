<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Department;

use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Department\DepartmentService;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
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
