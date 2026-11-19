<?php

declare(strict_types=1);

namespace Shared\Domain\Department\LandingPage\ViewModel;

use Shared\Controller\Admin\Upload\DepartmentUploadController;
use Shared\Domain\Department\Department;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function hash;

final readonly class DepartmentLandingPageViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(Department $department): DepartmentLandingPage
    {
        return new DepartmentLandingPage(
            departmentId: $department->getId(),
            name: $department->getName(),
            deleteLogoEndpoint: $this->urlGenerator->generate(
                'api_uploader_department_remove_logo',
                ['departmentId' => $department->getId()],
            ),
            logoEndpoint: $this->urlGenerator->generate(
                'app_admin_department_logo_download',
                [
                    'id' => $department->getId(),
                    'cacheKey' => hash('sha256', (string) $department->getUpdatedAt()->getTimestamp()),
                ],
            ),
            uploadLogoEndpoint: $this->urlGenerator->generate(
                DepartmentUploadController::ROUTE_NAME_UPLOAD,
                ['departmentId' => $department->getId()],
            ),
            hasLogo: $department->getFileInfo()->isUploaded(),
        );
    }
}
