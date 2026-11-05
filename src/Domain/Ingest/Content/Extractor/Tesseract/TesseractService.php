<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content\Extractor\Tesseract;

use Psr\Log\LoggerInterface;
use Shared\Service\ProcessFactory;

/**
 * Generates a hi-res PNG image of a (single-page) PDF file, and use tesseract to extract text from it by OCR.
 * This will allow to extract text from PDFs that are not text-based (e.g. scanned documents).
 */
class TesseractService
{
    public const string PDFTOPPM_PATH = '/usr/bin/pdftoppm';
    public const string TESSERACT_PATH = '/usr/bin/tesseract';
    public const string PDFTOPPM_INPUT_FROM_STDIN = '-';
    public const string TESSERACT_INPUT_FROM_STDIN = 'stdin';

    public function __construct(
        protected LoggerInterface $logger,
        protected ProcessFactory $processFactory,
    ) {
    }

    /**
     * @param resource $source
     */
    public function extract($source): string
    {
        $params = [self::PDFTOPPM_PATH, '-png', '-r', '300', '-singlefile', self::PDFTOPPM_INPUT_FROM_STDIN];

        $process = $this->processFactory->create($params);
        $process->setInput($source);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('pdftoppm failed', [
                'error_output' => $process->getErrorOutput(),
            ]);

            return '';
        }

        $png = $process->getOutput();

        $params = [self::TESSERACT_PATH, self::TESSERACT_INPUT_FROM_STDIN, 'stdout'];

        $process = $this->processFactory->create($params);
        $process->setInput($png);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Tesseract failed', [
                'error_output' => $process->getErrorOutput(),
            ]);

            return '';
        }

        return $process->getOutput();
    }
}
