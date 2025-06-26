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
        '/assets/department/{id}/{file}',
        name: 'app_department_assets_download',
        methods: ['GET'],
    )]
    public function download(
        #[MapEntity()] Department $department,
        string $file,
    ): StreamedResponse {
        $stream = $this->departmentFileService->getFileAsStream($department, $file);

        return $this->downloadHelper->getReponseForEntityAndStream($department, $stream);
    }
}
