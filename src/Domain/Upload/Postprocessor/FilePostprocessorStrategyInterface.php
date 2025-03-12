<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\UploadedFile;

interface FilePostprocessorStrategyInterface
{
    public function process(UploadedFile $file, WooDecision $dossier, string $documentId, ?string $fileType = null): void;

    public function canProcess(UploadedFile $file, WooDecision $dossier): bool;
}
