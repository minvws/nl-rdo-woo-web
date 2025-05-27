<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\Archiver\ArchiveNamer;
use App\Domain\Publication\BatchDownload\Archiver\ZipStreamFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\S3\StreamFactory;
use App\Service\DownloadFilenameGenerator;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webmozart\Assert\Assert;

readonly class OnDemandZipGenerator
{
    public function __construct(
        private BatchDownloadService $batchDownloadService,
        private StreamFactory $streamFactory,
        private ZipStreamFactory $zipStreamFactory,
        private ArchiveNamer $archiveNamer,
        private DownloadFilenameGenerator $filenameGenerator,
        private string $documentBucket,
        private S3Client $s3Client,
    ) {
    }

    public function getStreamedResponse(BatchDownloadScope $scope): StreamedResponse
    {
        $type = $this->batchDownloadService->getType($scope);

        /** @var Document[] $documents */
        $documents = $type->getDocumentsQuery($scope)->getQuery()->getResult();
        $baseName = $type->getFileBaseName($scope);
        $this->s3Client->registerStreamWrapperV2();

        return new StreamedResponse(
            function () use ($documents, $baseName) {
                $zip = $this->zipStreamFactory->forStreamingArchive(
                    $this->archiveNamer->getArchiveNameForStream($baseName),
                );
                $zip->addDirectory($baseName);

                foreach ($documents as $document) {
                    $path = $document->getFileInfo()->getPath();
                    Assert::string($path);

                    $documentStream = $this->streamFactory->createReadOnlyStream($this->documentBucket, $path);

                    $zip->addFileFromPsr7Stream(
                        fileName: $baseName . DIRECTORY_SEPARATOR . $this->filenameGenerator->getFileName($document),
                        stream: $documentStream,
                    );
                }

                $zip->finish();
            }
        );
    }

    public function getDetails(BatchDownloadScope $scope): DownloadDetails
    {
        $type = $this->batchDownloadService->getType($scope);
        $baseName = $type->getFileBaseName($scope);

        /** @var array{doc_count:int, total_size: string} $stats */
        $stats = $type->getDocumentsQuery($scope)
            ->select('count(doc.id) as doc_count, sum(doc.fileInfo.size) as total_size')
            ->getQuery()
            ->getSingleResult();

        return new DownloadDetails(
            name: $this->archiveNamer->getArchiveNameForStream($baseName),
            documentCount: $stats['doc_count'],
            totalDocumentSize: (int) $stats['total_size'],
        );
    }
}
