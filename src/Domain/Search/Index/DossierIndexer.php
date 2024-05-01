<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Covenant\CovenantMapper;
use App\Domain\Search\Index\WooDecision\WooDecisionMapper;
use App\Service\Elastic\ElasticService;

readonly class DossierIndexer
{
    public function __construct(
        private ElasticService $elasticService,
        private CovenantMapper $covenantMapper,
        private WooDecisionMapper $wooDecisionMapper,
    ) {
    }

    public function index(AbstractDossier $dossier, bool $updateSubItems = true): void
    {
        $doc = $this->map($dossier);

        $this->elasticService->updateDoc(
            $dossier->getDossierNr(),
            $doc,
        );

        if ($updateSubItems) {
            $this->elasticService->updateAllDocumentsForDossier($dossier, $doc->getFieldValues());
        }
    }

    public function map(AbstractDossier $dossier): ElasticDocument
    {
        return match (true) {
            $dossier instanceof WooDecision => $this->wooDecisionMapper->map($dossier),
            $dossier instanceof Covenant => $this->covenantMapper->map($dossier),
            default => throw IndexException::forUnsupportedDossierType($dossier->getType()),
        };
    }
}
