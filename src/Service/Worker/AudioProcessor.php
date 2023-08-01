<?php

declare(strict_types=1);

namespace App\Service\Worker;

use App\Entity\Document;
use App\Service\Ingest\IngestLogger;
use App\Service\Worker\Audio\Extractor\AudioExtractorInterface;
use App\Service\Worker\Audio\Extractor\WaveImageExtractor;

class AudioProcessor
{
    public function __construct(
        private readonly IngestLogger $ingestLogger,
        private readonly WaveImageExtractor $waveImageExtractor,
        private readonly AudioExtractorInterface $audioExtractor,
    ) {
    }

    public function process(Document $document, bool $forceRefresh = false): void
    {
        $this->waveImageExtractor->extract($document, $forceRefresh);
        $this->ingestLogger->success($document, 'audio/image', 'Extracted wave file image from audio');

        $this->audioExtractor->extract($document, $forceRefresh);
        $this->ingestLogger->success($document, 'audio/text ', 'Extracted text from audio');
    }
}
