<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftoppm;

use Shared\Service\ProcessFactory;

class PdftoppmService
{
    public const string PDFTOPPM_PATH = '/usr/bin/pdftoppm';
    public const string INPUT_FROM_STDIN = '-';

    public function __construct(
        private readonly ProcessFactory $processFactory,
    ) {
    }

    public function createThumbnail(string $pdf): PdftoppmThumbnailResult
    {
        $params = [
            self::PDFTOPPM_PATH,
            '-png',
            '-scale-to',
            '200',
            '-r',
            '150',
            '-singlefile',
            self::INPUT_FROM_STDIN,
        ];

        $process = $this->processFactory->create($params);
        $process->setInput($pdf);
        $exitCode = $process->run();

        return new PdftoppmThumbnailResult(
            exitCode: $exitCode,
            params: $params,
            errorMessage: $process->isSuccessful() ? null : $process->getErrorOutput(),
            thumbnail: $process->isSuccessful() ? $process->getOutput() : null,
        );
    }
}
