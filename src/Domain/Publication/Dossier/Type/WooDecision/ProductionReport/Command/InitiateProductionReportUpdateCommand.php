<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\Command;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class InitiateProductionReportUpdateCommand
{
    public function __construct(
        public WooDecision $dossier,
        public UploadedFile $upload,
    ) {
    }
}
