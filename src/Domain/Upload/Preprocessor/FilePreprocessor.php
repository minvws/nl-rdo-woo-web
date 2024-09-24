<?php

declare(strict_types=1);

namespace App\Domain\Upload\Preprocessor;

use App\Domain\Upload\UploadedFile;

readonly class FilePreprocessor
{
    /**
     * @param iterable<array-key,FilePreprocessorStrategyInterface> $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    /**
     * @return \Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): \Generator
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($file)) {
                yield from $strategy->process($file);

                return;
            }
        }

        // If none of the strategies can process the file, we want a iterable of the original file:
        yield $file;
    }
}
