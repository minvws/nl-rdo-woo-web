<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;

final class UploaderController extends AbstractController
{
    public function __construct(private CustomDropzoneController $dropzoneController)
    {
    }

    #[Route('/balie/uploader', name: '_uploader_upload_general', format: 'json', methods: ['POST', 'PUT', 'PATCH'])]
    #[IsGranted('AuthMatrix.dossier.update')]
    public function upload(Request $request): JsonResponse
    {
        $this->isChunkedWorkaround($request);

        return $this->dropzoneController->upload();
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
