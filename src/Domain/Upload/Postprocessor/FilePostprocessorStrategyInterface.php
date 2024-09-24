<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor;

use App\Domain\Upload\UploadedFile;
use App\Entity\Dossier;

interface FilePostprocessorStrategyInterface
{
    public function process(UploadedFile $file, Dossier $dossier): void;

    public function canProcess(UploadedFile $file, Dossier $dossier): bool;
}
