<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Upload\Postprocessor\FilePostprocessor;
use App\Domain\Upload\Postprocessor\NoMatchingFilePostprocessorException;
use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\UploadedFile;
use App\Entity\Dossier;
use Psr\Log\LoggerInterface;

readonly class FileProcessor
{
    public function __construct(
        private LoggerInterface $logger,
        private FilePreprocessor $filePreprocessor,
        private FilePostprocessor $filePostprocessor,
    ) {
    }

    public function process(UploadedFile $file, Dossier $dossier): void
    {
        foreach ($this->filePreprocessor->process($file) as $file) {
            try {
                $this->filePostprocessor->process($file, $dossier);
            } catch (NoMatchingFilePostprocessorException $e) {
                $this->logger->error('No matching file Postprocessor found', [
                    'filename' => $e->fileName,
                    'dossierId' => $e->dossierId,
                ]);
            }
        }
    }
}
