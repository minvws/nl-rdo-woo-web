<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Domain\Department\DepartmentFileService;
use App\Entity\Department;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class DepartmentAssetsController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
        private readonly DepartmentFileService $departmentFileService,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/assets/department/{id}/logo',
        name: 'app_department_logo_download',
        methods: ['GET'],
    )]
    public function downloadLogo(#[MapEntity()] Department $department): StreamedResponse
    {
        $stream = $this->departmentFileService->getLogoAsStream($department);

        return $this->downloadHelper->getReponseForEntityAndStream($department, $stream);
    }
}
