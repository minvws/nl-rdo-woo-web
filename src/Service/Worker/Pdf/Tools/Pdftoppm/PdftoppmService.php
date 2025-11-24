<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftoppm;

use Symfony\Component\Process\Process;

class PdftoppmService
{
    public const PDFTOPPM_PATH = '/usr/bin/pdftoppm';

    public function createThumbnail(string $sourcePdf, string $targetPath): PdftoppmThumbnailResult
    {
        $params = [
            self::PDFTOPPM_PATH,
            '-png',
            '-scale-to',
            '200',
            '-r',
            '150',
            '-singlefile',
            $sourcePdf,
            $targetPath,
        ];

        $process = $this->getNewProcess($params);
        $exitCode = $process->run();

        return new PdftoppmThumbnailResult(
            exitCode: $exitCode,
            params: $params,
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
            sourcePdf: $sourcePdf,
            targetPath: $targetPath,
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
