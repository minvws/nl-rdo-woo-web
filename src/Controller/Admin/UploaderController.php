<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UploaderController extends AbstractController
{
    public function __construct(private CustomDropzoneController $dropzoneController)
    {
    }

    #[Route('/balie/uploader', name: '_uploader_upload_general', format: 'json', methods: ['POST', 'PUT', 'PATCH'])]
    #[IsGranted('AuthMatrix.dossier.update')]
    public function upload(): JsonResponse
    {
        return $this->dropzoneController->upload();
    }
}
