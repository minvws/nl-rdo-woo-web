<?php

declare(strict_types=1);

namespace App\Domain\Department\LandingPage\ViewModel;

use App\Entity\Department;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class DepartmentLandingPageViewFactory
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(Department $department): DepartmentLandingPage
    {
        return new DepartmentLandingPage(
            name: $department->getName(),
            deleteLogoEndpoint: $this->urlGenerator->generate('api_uploader_department_remove_logo', ['departmentId' => $department->getId()]),
            logoEndpoint: $this->getLogoEndpoint($department),
            uploadLogoEndpoint: $this->urlGenerator->generate('_uploader_upload_department', ['departmentId' => $department->getId()]),
        );
    }

    private function getLogoEndpoint(Department $department): ?string
    {
        if (! $department->getFileInfo()->isUploaded()) {
            return null;
        }

        return $this->urlGenerator->generate(
            'app_admin_department_assets_download',
            ['id' => $department->getId(), 'file' => $department->getFileInfo()->getName()],
        );
    }
}
