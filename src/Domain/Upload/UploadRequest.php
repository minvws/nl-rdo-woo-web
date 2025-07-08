<?php

declare(strict_types=1);

namespace App\Domain\Upload;

use App\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

readonly class UploadRequest
{
    private const string CHUNK_INDEX_PARAM = 'chunkindex';
    private const string TOTAL_CHUNK_COUNT_PARAM = 'totalchunkcount';
    private const string UUID_PARAM = 'uuid';
    private const string GROUP_ID_PARAM = 'groupId';

    public function __construct(
        public int $chunkIndex,
        public int $chunkCount,
        public string $uploadId,
        public UploadedFile $uploadedFile,
        public UploadGroupId $groupId,
        public InputBag $additionalParameters,
    ) {
    }

    public function getFilename(): string
    {
        return $this->uploadedFile->getClientOriginalName();
    }

    public function getMimeType(): string
    {
        return $this->uploadedFile->getClientMimeType();
    }

    public static function fromHttpRequest(Request $request): self
    {
        $additionalParameters = clone $request->query;
        $additionalParameters->remove(self::CHUNK_INDEX_PARAM);
        $additionalParameters->remove(self::TOTAL_CHUNK_COUNT_PARAM);
        $additionalParameters->remove(self::UUID_PARAM);
        $additionalParameters->remove(self::GROUP_ID_PARAM);

        if ($request->getPayload()->has('dossierId')) {
            $additionalParameters->set('dossierId', $request->getPayload()->get('dossierId'));
        }

        if ($request->getPayload()->has('departmentId')) {
            $additionalParameters->set('departmentId', $request->getPayload()->get('departmentId'));
        }

        $uploadedFile = $request->files->get('file');
        Assert::isInstanceOf($uploadedFile, UploadedFile::class);

        return new self(
            chunkIndex: $request->getPayload()->getInt(self::CHUNK_INDEX_PARAM),
            chunkCount: $request->getPayload()->getInt(self::TOTAL_CHUNK_COUNT_PARAM),
            uploadId: $request->getPayload()->getString(self::UUID_PARAM),
            uploadedFile: $uploadedFile,
            groupId: UploadGroupId::from($request->getPayload()->getString(self::GROUP_ID_PARAM)),
            additionalParameters: $additionalParameters,
        );
    }

    public function isChunked(): bool
    {
        return $this->chunkCount > 1;
    }

    public function hasMoreChunksToFollow(): bool
    {
        return $this->chunkIndex + 1 < $this->chunkCount;
    }
}
