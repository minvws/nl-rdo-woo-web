<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Archiver;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

interface BatchArchiver
{
    public function start(BatchDownloadTypeInterface $type, BatchDownload $batch, string $batchFileName): void;

    public function addDocument(Document $document): bool;

    public function finish(): false|BatchArchiverResult;

    public function cleanup(): bool;
}
