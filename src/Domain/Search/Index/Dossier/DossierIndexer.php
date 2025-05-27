<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\Dossier\Mapper\ElasticDossierMapperInterface;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\IndexException;
use App\Domain\Search\Index\Updater\NestedDossierIndexUpdater;
use App\Service\Elastic\ElasticService;

readonly class DossierIndexer
{
    /**
     * @param iterable<ElasticDossierMapperInterface> $mappers
     */
    public function __construct(
        private ElasticService $elasticService,
        private NestedDossierIndexUpdater $nestedDossierUpdater,
        private iterable $mappers,
    ) {
    }

    public function index(AbstractDossier $dossier, bool $updateSubItems = true): void
    {
        $doc = $this->map($dossier);

        $this->elasticService->updateDocument($doc);

        if ($updateSubItems) {
            $this->nestedDossierUpdater->update($dossier, $doc->getDocumentValues());
        }
    }

    public function map(AbstractDossier $dossier): ElasticDocument
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($dossier)) {
                return $mapper->map($dossier);
            }
        }

        throw IndexException::forUnsupportedDossierType($dossier->getType());
    }
}
