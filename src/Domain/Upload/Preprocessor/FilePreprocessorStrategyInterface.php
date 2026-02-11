<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Preprocessor;

use Generator;
use Shared\Domain\Upload\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.upload.preprocessor.strategy')]
interface FilePreprocessorStrategyInterface
{
    /**
     * @return Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): Generator;

    public function canProcess(UploadedFile $file): bool;
}
