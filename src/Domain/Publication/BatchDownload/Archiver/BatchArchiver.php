<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload\Archiver;

use Shared\Domain\Publication\BatchDownload\BatchDownload;
use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

interface BatchArchiver
{
    public function start(BatchDownloadTypeInterface $type, BatchDownload $batch, string $batchFileName): void;

    public function addDocument(Document $document): bool;

    public function finish(): false|BatchArchiverResult;

    public function cleanup(): bool;
}
