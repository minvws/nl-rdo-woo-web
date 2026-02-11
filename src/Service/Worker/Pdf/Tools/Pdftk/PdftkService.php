<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftk;

use Symfony\Component\Process\Process;

use function preg_match;

use const PREG_UNMATCHED_AS_NULL;

class PdftkService
{
    public const PDFTK_PATH = '/usr/bin/pdftk';

    public function extractPage(string $sourcePdf, int $pageNr, string $targetPath): PdftkPageExtractResult
    {
        $params = [self::PDFTK_PATH, $sourcePdf, 'cat', $pageNr, 'output', $targetPath];

        $process = $this->getNewProcess($params);
        $exitCode = $process->run();

        return new PdftkPageExtractResult(
            exitCode: $exitCode,
            params: $params,
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
            sourcePdf: $sourcePdf,
            pageNr: $pageNr,
            targetPath: $targetPath,
        );
    }

    public function extractNumberOfPages(string $sourcePdf): PdftkPageCountResult
    {
        $params = [self::PDFTK_PATH, $sourcePdf, 'dump_data'];

        $process = $this->getNewProcess($params);
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
            sourcePdf: $sourcePdf,
            numberOfPages: $numberOfPages ?? null,
        );
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
