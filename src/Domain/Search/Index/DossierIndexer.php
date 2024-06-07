<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\Elastic\ElasticService;

readonly class DossierIndexer
{
    /**
     * @var iterable<ElasticDossierMapperInterface>
     */
    private iterable $mappers;

    /**
     * @param iterable<ElasticDossierMapperInterface> $mappers
     */
    public function __construct(
        private ElasticService $elasticService,
        iterable $mappers,
    ) {
        $this->mappers = $mappers;
    }

    public function index(AbstractDossier $dossier, bool $updateSubItems = true): void
    {
        $doc = $this->map($dossier);

        $this->elasticService->updateDoc(
            $dossier->getDossierNr(),
            $doc,
        );

        if ($updateSubItems) {
            $this->elasticService->updateAllDocumentsForDossier($dossier, $doc->getDocumentValues());
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
