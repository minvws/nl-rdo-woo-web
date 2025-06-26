<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Department\DepartmentFileService;
use App\Domain\Department\DepartmentService;
use App\Entity\Department;
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
        '/balie/assets/department/{id}/{file}',
        name: 'app_admin_department_assets_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.department_landing_page.update')]
    public function download(
        #[MapEntity()] Department $department,
        string $file,
    ): StreamedResponse {
        if (! $this->departmentService->userCanEditLandingpage($department)) {
            throw $this->createAccessDeniedException();
        }

        $stream = $this->departmentFileService->getFileAsStream($department, $file);

        return $this->downloadHelper->getReponseForEntityAndStream($department, $stream);
    }
}
