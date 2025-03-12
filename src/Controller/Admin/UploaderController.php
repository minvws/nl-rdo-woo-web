<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;

final class UploaderController extends AbstractController
{
    #[Route('/balie/uploader', name: '_uploader_upload_general', format: 'json', methods: ['POST', 'PUT', 'PATCH'])]
    #[IsGranted('AuthMatrix.dossier.update')]
    public function upload(
        #[Autowire(service: 'oneup_uploader.controller.general')]
        CustomDropzoneController $dropzoneController,
        Request $request,
    ): JsonResponse {
        $this->isChunkedWorkaround($request);

        return $dropzoneController->upload();
    }

    #[Route('/balie/uploader/woo-decision/{dossierId}', name: '_uploader_upload_woo_decision', format: 'json', methods: ['POST', 'PUT', 'PATCH'])]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function uploadDossier(
        #[Autowire(service: 'oneup_uploader.controller.woo_decision')]
        CustomDropzoneController $dropzoneController,
        #[MapEntity(mapping: ['dossierId' => 'id'])]
        WooDecision $dossier,
        Request $request,
    ): JsonResponse {
        unset($dossier);

        $this->isChunkedWorkaround($request);

        return $dropzoneController->upload();
    }

    /**
     * This is a workaround for the OneupUploaderBundle's default dropzoneController. The dropzoneController logic
     * determines if the request is a chunked based on the existence of the chunkindex request value. This is not ideal
     * because it would always emit the chunked upload event (even when totalchunkcount equals 1).
     */
    private function isChunkedWorkaround(Request $request): void
    {
        $totalChunkCount = $request->request->get('totalchunkcount');
        Assert::numeric($totalChunkCount);

        $totalChunkCount = (int) $totalChunkCount;
        if ($totalChunkCount === 1) {
            $request->request->remove('chunkindex');
        }
    }
}
