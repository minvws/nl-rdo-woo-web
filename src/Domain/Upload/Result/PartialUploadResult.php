<?php

declare(strict_types=1);

namespace App\Domain\Upload\Result;

use App\Domain\Upload\UploadRequest;
use App\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class PartialUploadResult implements UploadResultInterface
{
    public function __construct(
        public string $uploadId,
        public string $filename,
        public UploadGroupId $groupId,
    ) {
    }

    public static function create(UploadRequest $request): self
    {
        return new self(
            $request->uploadId,
            $request->getFilename(),
            $request->groupId,
        );
    }

    public function toJsonResponse(): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'uploadUuid' => $this->uploadId,
                'originalName' => $this->filename,
                'groupId' => $this->groupId,
            ],
        ]);
    }
}
