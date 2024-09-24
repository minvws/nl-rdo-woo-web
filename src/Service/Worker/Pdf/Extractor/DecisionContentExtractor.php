<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\DecisionDocument;
use App\Service\Elastic\ElasticService;

readonly class DecisionContentExtractor
{
    public function __construct(
        private ContentExtractService $contentExtractService,
        private ElasticService $elasticService,
    ) {
    }

    public function extract(WooDecision $dossier, DecisionDocument $decision, bool $forceRefresh): void
    {
        $extracts = $this->contentExtractService->getExtracts(
            $decision,
            ContentExtractOptions::create()->withAllExtractors()->withRefresh($forceRefresh),
        );

        $this->elasticService->updateDossierDecisionContent(
            $dossier,
            $extracts->getCombinedContent(),
        );
    }
}
