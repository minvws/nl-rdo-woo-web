<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\SubType;

use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\Index\IndexException;
use Shared\Domain\Search\Index\SubType\Mapper\ElasticSubTypeMapperInterface;
use Shared\Domain\Search\Index\Updater\PageIndexUpdater;
use Shared\Service\Elastic\ElasticService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class SubTypeIndexer
{
    /**
     * @param iterable<ElasticSubTypeMapperInterface> $mappers
     */
    public function __construct(
        private ElasticService $elasticService,
        private PageIndexUpdater $pageIndexUpdater,
        #[AutowireIterator('woo_platform.search.index.subtype_mapper')]
        private iterable $mappers,
    ) {
    }

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function index(object $entity, ?array $metadata = null, ?array $pages = null): void
    {
        $mapper = $this->getMapper($entity);

        $this->elasticService->updateDocument(
            $mapper->map($entity, $metadata, $pages)
        );
    }

    public function remove(object $entity): void
    {
        $this->elasticService->removeDocument(
            ElasticDocumentId::forObject($entity),
        );
    }

    public function updatePage(object $entity, int $pageNr, string $content): void
    {
        $this->pageIndexUpdater->update(
            ElasticDocumentId::forObject($entity),
            $pageNr,
            $content
        );
    }

    private function getMapper(object $entity): ElasticSubTypeMapperInterface
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($entity)) {
                return $mapper;
            }
        }

        throw IndexException::forUnsupportedSubType($entity);
    }
}
