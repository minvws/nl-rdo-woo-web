<?php

declare(strict_types=1);

namespace App\Domain\Upload\Handler\S3;

use App\Domain\Upload\UploadRequest;
use Aws\S3\S3Client;
use Aws\S3\S3UriParser;
use GuzzleHttp\Psr7\Stream;
use Webmozart\Assert\Assert;

readonly class S3UploadHelper
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
    ) {
    }

    public function createMultipartUpload(UploadRequest $request): string
    {
        $result = $this->s3Client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
        ]);

        Assert::true($result->hasKey('UploadId'));

        /** @var string */
        return $result->get('UploadId');
    }

    public function uploadPart(UploadRequest $request, string $s3UploadId): void
    {
        $this->s3Client->uploadPart([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
            'PartNumber' => $request->chunkIndex + 1,
            'UploadId' => $s3UploadId,
            'Body' => file_get_contents($request->uploadedFile->getRealPath()),
        ]);
    }

    public function completeMultipartUpload(UploadRequest $request, string $s3UploadId): int
    {
        $partsResult = $this->s3Client->listParts([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
            'UploadId' => $s3UploadId,
        ]);

        $this->s3Client->completeMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
            'UploadId' => $s3UploadId,
            'MultipartUpload' => [
                'Parts' => $partsResult['Parts'],
            ],
        ]);

        $resultObject = $this->s3Client->headObject([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
        ]);

        Assert::true($resultObject->hasKey('ContentLength'));

        /** @var int */
        return $resultObject->get('ContentLength');
    }

    public function copyUploadToPath(string $uploadId, string $targetPath): void
    {
        $parser = new S3UriParser();
        $targetPathParts = $parser->parse($targetPath);

        $key = $targetPathParts['key'];
        Assert::string($key);

        $this->s3Client->copyObject([
            'Bucket' => $targetPathParts['bucket'],
            'Key' => urldecode($key),
            'CopySource' => sprintf('%s/%s', $this->bucket, $uploadId),
        ]);
    }

    public function deleteUpload(string $uploadId): void
    {
        $this->s3Client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ]);
    }

    public function uploadFile(UploadRequest $request): void
    {
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $request->uploadId,
            'SourceFile' => $request->uploadedFile->getRealPath(),
        ]);
    }

    public function readStream(string $uploadId, ?int $limit): Stream
    {
        $params = [
            'Bucket' => $this->bucket,
            'Key' => $uploadId,
        ];

        if ($limit !== null) {
            $params['Range'] = 'bytes=0-' . $limit;
        }

        /** @var Stream */
        return $this->s3Client->getObject($params)->get('Body');
    }
}
