<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Archiver;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\S3\StreamFactory;
use Shared\Service\DownloadFilenameGenerator;
use Webmozart\Assert\Assert;
use ZipStream\Exception as ZipStreamException;
use ZipStream\ZipStream;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class ZipStreamBatchArchiver implements BatchArchiver
{
    private ZipStream $zipStream;

    private StreamInterface $zipFile;

    private string $batchFileName;

    private int $fileCount = 0;

    public function __construct(
        private readonly string $batchBucket,
        private readonly string $documentBucket,
        private readonly S3Client $s3Client,
        private readonly ZipStreamFactory $zipStreamFactory,
        private readonly DownloadFilenameGenerator $filenameGenerator,
        private readonly LoggerInterface $logger,
        private readonly StreamFactory $streamFactory,
    ) {
    }

    public function start(BatchDownloadTypeInterface $type, BatchDownload $batch, string $batchFileName): void
    {
        $this->batchFileName = $batchFileName;
        $this->fileCount = 0;

        try {
            $this->s3Client->registerStreamWrapperV2();
            $this->zipFile = $this->streamFactory->createWriteOnlyStream($this->batchBucket, $this->batchFileName);
            $this->zipStream = $this->zipStreamFactory->create($this->zipFile);
        } catch (ZipStreamException | AwsException $e) {
            $this->logException($e);
            $this->cleanup();

            throw $e;
        }
    }

    public function addDocument(Document $document): bool
    {
        $path = $document->getFileInfo()->getPath();
        Assert::string($path);

        $documentResource = $this->streamFactory->createReadOnlyStream($this->documentBucket, $path);

        try {
            $this->zipStream->addFileFromPsr7Stream(
                fileName: $this->filenameGenerator->getFileName($document),
                stream: $documentResource,
            );
        } catch (ZipStreamException | AwsException $e) {
            $this->logException($e);
            $this->cleanup();

            return false;
        } finally {
            $documentResource->close();
        }

        $this->fileCount++;

        return true;
    }

    public function finish(): false|BatchArchiverResult
    {
        try {
            $fileSize = 0;
            if (isset($this->zipStream)) {
                $fileSize = $this->zipStream->finish();
                unset($this->zipStream);
            }
        } catch (ZipStreamException $e) {
            $this->logException($e);
            $this->cleanup();

            return false;
        } finally {
            if (isset($this->zipFile)) {
                $this->zipFile->close();
            }
        }

        return new BatchArchiverResult(
            filename: $this->batchFileName,
            size: $fileSize,
            fileCount: $this->fileCount,
        );
    }

    public function cleanup(): bool
    {
        if (isset($this->zipStream)) {
            try {
                $this->zipStream->finish();
                unset($this->zipStream);
            } catch (ZipStreamException) {
                // @SuppressWarnings("PHPMD.EmptyCatchBlock")
            }
        }

        if (isset($this->zipFile)) {
            $this->zipFile->close();
        }

        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->batchBucket,
                'Key' => $this->batchFileName,
            ]);
        } catch (AwsException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }

    private function logException(\Throwable $e): void
    {
        $this->logger->error(sprintf('"%s" exception thrown', $e::class), [
            'exceptionMessage' => $e->getMessage(),
        ]);
    }
}
