<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Generates a hi-res PNG image of a (single-page) PDF file, and use tesseract to extract text from it by OCR.
 * This will allow to extract text from PDFs that are not text-based (e.g. scanned documents).
 */
class Tesseract
{
    protected LoggerInterface $logger;
    protected FileUtils $fileUtils;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->fileUtils = new FileUtils();
    }

    public function extract(string $sourcePdfPath): string
    {
        $tempDir = $this->fileUtils->createTempDir();
        $targetPngPath = $tempDir . '/page';

        // Create hi-res page
        $params = ['/usr/bin/pdftoppm', '-png', '-r', '300', '-singlefile', $sourcePdfPath, $targetPngPath];
        $this->logger->debug('EXEC: ' . join(' ', $params));
        $process = new Process($params);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('pdftoppm failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->fileUtils->deleteTempDirectory($tempDir);

            return '';
        }

        $params = ['/usr/bin/tesseract', $targetPngPath . '.png', 'stdout'];
        $this->logger->debug('EXEC: ' . join(' ', $params));
        $process = new Process($params);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Tesseract failed', [
                'sourcePath' => $sourcePdfPath,
                'targetPngPath' => $targetPngPath . '.png',
                'error_output' => $process->getErrorOutput(),
            ]);

            $this->fileUtils->deleteTempDirectory($tempDir);

            return '';
        }

        $this->logger->debug('Tesseract content: ' . strlen($process->getOutput()) . ' bytes');

        $this->fileUtils->deleteTempDirectory($tempDir);

        return $process->getOutput();
    }
}
