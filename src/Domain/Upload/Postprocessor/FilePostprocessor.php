<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor;

use App\Domain\Upload\UploadedFile;
use App\Entity\Dossier;

readonly class FilePostprocessor
{
    /**
     * @param iterable<array-key,FilePostprocessorStrategyInterface> $strategies
     */
    public function __construct(private iterable $strategies)
    {
    }

    public function process(UploadedFile $file, Dossier $dossier): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->canProcess($file, $dossier)) {
                $strategy->process($file, $dossier);

                return;
            }
        }

        throw NoMatchingFilePostprocessorException::create($file, $dossier);
    }
}
