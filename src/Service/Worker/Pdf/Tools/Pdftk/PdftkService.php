<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftk;

use Shared\Service\ProcessFactory;

use function preg_match;

use const PREG_UNMATCHED_AS_NULL;

class PdftkService
{
    public const string PDFTK_PATH = '/usr/bin/pdftk';
    public const string INPUT_FROM_STDIN = '-';
    public const string OUTPUT_TO_STDOUT = '-';

    public function __construct(
        private readonly ProcessFactory $processFactory,
    ) {
    }

    /**
     * @param resource $source
     */
    public function extractPage($source, int $pageNr): PdftkPageExtractResult
    {
        $params = [self::PDFTK_PATH, self::INPUT_FROM_STDIN, 'cat', (string) $pageNr, 'output', self::OUTPUT_TO_STDOUT];

        $process = $this->processFactory->create($params);
        $process->setInput($source);
        $exitCode = $process->run();

        return new PdftkPageExtractResult(
            exitCode: $exitCode,
            params: $params,
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
            pageNr: $pageNr,
            pageContents: $process->isSuccessful() ? $process->getOutput() : null,
        );
    }

    /**
     * @param resource $source
     */
    public function extractNumberOfPages($source): PdftkPageCountResult
    {
        $params = [self::PDFTK_PATH, self::INPUT_FROM_STDIN, 'dump_data'];

        $process = $this->processFactory->create($params);
        $process->setInput($source);
        $exitCode = $process->run();

        if ($process->isSuccessful()) {
            $result = preg_match('/NumberOfPages: (\d+)/', $process->getOutput(), $matches, PREG_UNMATCHED_AS_NULL);
            if ($result === false || $result === 0) {
                throw PdftkRuntimeException::noPageCountResultFound();
            }

            $numberOfPages = (int) $matches[1];
        }

        return new PdftkPageCountResult(
            exitCode: $exitCode,
            params: $params,
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
            numberOfPages: $numberOfPages ?? null,
        );
    }
}
