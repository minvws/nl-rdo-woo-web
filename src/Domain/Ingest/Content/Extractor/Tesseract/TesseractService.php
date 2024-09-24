<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tesseract;

use App\Service\Storage\LocalFilesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Generates a hi-res PNG image of a (single-page) PDF file, and use tesseract to extract text from it by OCR.
 * This will allow to extract text from PDFs that are not text-based (e.g. scanned documents).
 */
class TesseractService
{
    public const PDFTOPPM_PATH = '/usr/bin/pdftoppm';
    public const TESSERACT_PATH = '/usr/bin/tesseract';

    public function __construct(
        protected LoggerInterface $logger,
        protected LocalFilesystem $localFilesystem,
    ) {
    }

    public function extract(string $sourcePdfPath): string
    {
        $tempDir = $this->localFilesystem->createTempDir();
        if ($tempDir === false) {
            return '';
        }
        $targetPngPath = $tempDir . '/page';

        // Create hi-res page
        $params = [self::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', $sourcePdfPath, $targetPngPath];

        $process = $this->getNewProcess($params);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('pdftoppm failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->localFilesystem->deleteDirectory($tempDir);

            return '';
        }

        $params = [self::TESSERACT_PATH, $targetPngPath . '.png', 'stdout'];

        $process = $this->getNewProcess($params);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Tesseract failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->localFilesystem->deleteDirectory($tempDir);

            return '';
        }

        $this->localFilesystem->deleteDirectory($tempDir);

        return $process->getOutput();
    }

    /**
     * @param array<int,string|int> $params
     *
     * @codeCoverageIgnore
     */
    protected function getNewProcess(array $params): Process
    {
        return new Process($params);
    }
}
