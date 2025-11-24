<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\Dossier\Mapper\ElasticDossierMapperInterface;
use Shared\Domain\Search\Index\ElasticDocument;
use Shared\Domain\Search\Index\IndexException;
use Shared\Domain\Search\Index\Updater\NestedDossierIndexUpdater;
use Shared\Service\Elastic\ElasticService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class DossierIndexer
{
    /**
     * @param iterable<ElasticDossierMapperInterface> $mappers
     */
    public function __construct(
        private ElasticService $elasticService,
        private NestedDossierIndexUpdater $nestedDossierUpdater,
        #[AutowireIterator('woo_platform.search.index.dossier_mapper')]
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
