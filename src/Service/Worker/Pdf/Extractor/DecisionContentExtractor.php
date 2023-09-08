<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\DecisionDocument;
use App\Entity\Dossier;
use App\Entity\EntityWithFileInfo;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Tools\Tesseract;
use App\Service\Worker\Pdf\Tools\Tika;
use Predis\Client;

class DecisionContentExtractor
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly Tesseract $tesseract,
        private readonly Tika $tika,
        private readonly ElasticService $elasticService,
        private readonly Client $redis,
    ) {
    }

    public function extract(Dossier $dossier, DecisionDocument $decision, bool $forceRefresh): void
    {
        if ($forceRefresh || ! $this->isCached($decision)) {
            $content = $this->extractContent($decision);
            $this->setCachedContent($decision, $content);
        }

        $content = $this->getCachedContent($decision);

        $this->elasticService->updateDossierDecisionContent($dossier, $content);
    }

    private function extractContent(DecisionDocument $decision): string
    {
        $localFilePath = $this->documentStorage->downloadDocument($decision);
        if (! $localFilePath) {
            throw new \RuntimeException('Failed to file to local storage for DecisionDocument ' . $decision->getId()->toBase58());
        }

        $tikaData = $this->tika->extract($localFilePath);
        $content = $tikaData['X-TIKA:content'] ?? '';
        $content .= "\n";
        $content .= $this->tesseract->extract($localFilePath);

        $this->documentStorage->removeDownload($localFilePath);

        return $content;
    }

    protected function getCachedContent(EntityWithFileInfo $entity): string
    {
        $key = $this->getCacheKey($entity);

        return strval($this->redis->get($key));
    }

    protected function setCachedContent(EntityWithFileInfo $entity, string $content): void
    {
        $this->redis->set(
            $this->getCacheKey($entity),
            $content
        );
    }

    protected function isCached(EntityWithFileInfo $entity): bool
    {
        $key = $this->getCacheKey($entity);

        return $this->redis->exists($key) === 1;
    }

    protected function getCacheKey(EntityWithFileInfo $entity): string
    {
        return $entity->getFileCacheKey() . '-content';
    }
}
