<?php

declare(strict_types=1);

namespace App\Domain\Upload\Preprocessor;

use App\Domain\Upload\UploadedFile;

interface FilePreprocessorStrategyInterface
{
    /**
     * @return \Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): \Generator;

    public function canProcess(UploadedFile $file): bool;
}
