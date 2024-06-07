<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Service\Elastic\ElasticService;

readonly class ElasticDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private ElasticService $elasticService,
    ) {
    }

    public function delete(AbstractDossier $dossier): void
    {
        $this->elasticService->removeDossier($dossier);
    }
}
