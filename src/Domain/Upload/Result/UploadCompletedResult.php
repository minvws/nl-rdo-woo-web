<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Result;

use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadRequest;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webmozart\Assert\Assert;

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

    public static function createFromUploadRequest(UploadRequest $request, int $size): self
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

    public static function createFromStreamUpload(StreamUpload $streamUpload): self
    {
        $size = $streamUpload->stream->getSize();
        Assert::notNull($size, 'Stream size must be known for stream uploads');

        return new self(
            $streamUpload->uploadId,
            $streamUpload->fileName,
            $streamUpload->groupId,
            'application/octet-stream',
            $size,
            $streamUpload->additionalParameters,
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
