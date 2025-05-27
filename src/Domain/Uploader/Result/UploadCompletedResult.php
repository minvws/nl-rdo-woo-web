<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Result;

use App\Domain\Uploader\UploadRequest;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class UploadCompletedResult implements UploadResultInterface
{
    public function __construct(
        public string $uploadId,
        public string $filename,
        public UploadGroupId $groupId,
        public string $mimeType,
        public int $size,
        public InputBag $additionalParameters,
    ) {
    }

    public static function create(UploadRequest $request, int $size): self
    {
        return new self(
            $request->uploadId,
            $request->getFilename(),
            $request->groupId,
            $request->getMimeType(),
            $size,
            $request->additionalParameters,
        );
    }

    public function toJsonResponse(): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'uploadUuid' => $this->uploadId,
                'originalName' => $this->filename,
                'groupId' => $this->groupId,
                'mimeType' => $this->mimeType,
                'size' => $this->size,
            ],
        ]);
    }
}
