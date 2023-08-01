<?php

declare(strict_types=1);

namespace App\Service\Worker\Audio\Extractor;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;

/**
 * Extractor that will extract the wave image from an audio file.
 */
class WaveImageExtractor
{
    protected LoggerInterface $logger;
    protected ThumbnailStorageService $thumbnailStorage;
    protected DocumentStorageService $documentStorage;
    protected FileUtils $fileUtils;

    public function __construct(LoggerInterface $logger, ThumbnailStorageService $thumbnailStorage, DocumentStorageService $documentStorage)
    {
        $this->logger = $logger;
        $this->thumbnailStorage = $thumbnailStorage;
        $this->documentStorage = $documentStorage;

        $this->fileUtils = new FileUtils();
    }

    public function extract(Document $document, bool $forceRefresh): void
    {
        if (! $forceRefresh && $this->thumbnailStorage->exists($document, 0)) {
            // Thumbnail already exists, and we are allowed to use it
            return;
        }

        $tempDir = $this->fileUtils->createTempDir();

        $localPath = $this->documentStorage->downloadDocument($document);
        if (! $localPath) {
            $this->logger->error('cannot download audio file from storage', [
                'document' => $document->getId(),
            ]);

            return;
        }

        $targetPath = $tempDir . '/wave-output.png';

        $script = <<< EOS
/usr/bin/ffmpeg -i $localPath -acodec pcm_s16le -ar 16000 -ac 1 -f wav - | 
/usr/bin/ffmpeg \
    -i - \
    -frames:v 1 \
    -c:a pcm_s16le \
    -ar 16000 -ac 1 \
    -filter_complex \
    "[0:a]aformat=channel_layouts=mono,compand=gain=-6,showwavespic=s=600x120:colors=#9cf42f[fg];color=s=600x120:color=#44582c, \
    drawgrid=width=iw/10:height=ih/5:color=#9cf42f@0.1[bg]; [bg][fg]overlay=format=rgb,drawbox=x=(iw-w)/2:y=(ih-h)/2:w=iw:h=1:color=#9cf42f" \
    $targetPath
EOS;

        // run shell script
        $params = [
            '/bin/sh',
            '-c',
            $script,
        ];

        $this->logger->debug('EXEC: ' . join(' ', $params));
        $process = new Process($params);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Failed to create wave image for audio', [
                'document' => $document->getId(),
                'localPath' => $localPath,
                'targetPath' => $targetPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->fileUtils->deleteTempDirectory($tempDir);
            $this->documentStorage->removeDownload($localPath);

            return;
        }

        $this->thumbnailStorage->store($document, new File($targetPath), 0);

        $this->fileUtils->deleteTempDirectory($tempDir);
        $this->documentStorage->removeDownload($localPath);
    }
}
