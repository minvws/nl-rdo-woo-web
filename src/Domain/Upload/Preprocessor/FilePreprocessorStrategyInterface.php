<?php

declare(strict_types=1);

namespace App\Domain\Upload\Preprocessor;

use App\Domain\Upload\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.upload.preprocessor.strategy')]
interface FilePreprocessorStrategyInterface
{
    /**
     * @return \Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): \Generator;

    public function canProcess(UploadedFile $file): bool;
}
