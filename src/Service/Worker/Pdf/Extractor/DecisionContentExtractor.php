<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\DecisionDocument;
use App\Entity\Dossier;
use App\Service\Elastic\ElasticService;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Service\Worker\Pdf\Tools\TikaService;

readonly class DecisionContentExtractor
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private TesseractService $tesseract,
        private TikaService $tika,
        private ElasticService $elasticService,
        private WorkerStatsService $statService,
    ) {
    }

    public function extract(Dossier $dossier, DecisionDocument $decision, bool $forceRefresh): void
    {
        // TODO: Cache is removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);
        $content = $this->extractContent($decision);

        $this->elasticService->updateDossierDecisionContent($dossier, $content);
    }

    private function extractContent(DecisionDocument $decision): string
    {
        /** @var string|false $localFilePath */
        $localFilePath = $this->statService->measure(
            'download.document',
            fn (): string|false => $this->entityStorageService->downloadEntity($decision),
        );
        if ($localFilePath === false) {
            throw new \RuntimeException('Failed to file to local storage for DecisionDocument ' . $decision->getId()->toBase58());
        }

        /** @var array<string,string> $tikaData */
        $tikaData = $this->statService->measure('tika', fn (): array => $this->tika->extract($localFilePath));

        /** @var string $tesseractContent */
        $tesseractContent = $this->statService->measure(
            'tesseract',
            fn () => $this->tesseract->extract($localFilePath),
        );

        $this->entityStorageService->removeDownload($localFilePath);

        return join("\n", [$tikaData['X-TIKA:content'] ?? '', $tesseractContent]);
    }
}
