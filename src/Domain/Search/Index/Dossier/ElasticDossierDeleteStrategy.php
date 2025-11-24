<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Shared\Service\Elastic\ElasticService;

readonly class ElasticDossierDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private ElasticService $elasticService,
    ) {
    }

    public function delete(AbstractDossier $dossier): void
    {
        $this->elasticService->removeDossier($dossier);
    }

    public function deleteWithOverride(AbstractDossier $dossier): void
    {
        $this->delete($dossier);
    }
}
