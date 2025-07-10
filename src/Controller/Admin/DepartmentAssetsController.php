<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Department\Department;
use App\Domain\Department\DepartmentFileService;
use App\Domain\Department\DepartmentService;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DepartmentAssetsController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DepartmentFileService $departmentFileService,
        private readonly DepartmentService $departmentService,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/balie/assets/department/{id}/logo',
        name: 'app_admin_department_logo_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.department_landing_page.update')]
    public function downloadLogo(#[MapEntity()] Department $department): StreamedResponse
    {
        if (! $this->departmentService->userCanEditLandingpage($department)) {
            throw $this->createAccessDeniedException();
        }

        $stream = $this->departmentFileService->getLogoAsStream($department);

        return $this->downloadHelper->getReponseForEntityAndStream($department, $stream);
    }
}
