<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Search\Index\IndexException;
use App\Domain\Search\Index\SubType\Mapper\ElasticSubTypeMapperInterface;
use App\Service\Elastic\ElasticService;

readonly class SubTypeIndexer
{
    /**
     * @var iterable<ElasticSubTypeMapperInterface>
     */
    private iterable $mappers;

    /**
     * @param iterable<ElasticSubTypeMapperInterface> $mappers
     */
    public function __construct(
        private ElasticService $elasticService,
        iterable $mappers,
    ) {
        $this->mappers = $mappers;
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
            $this->getDocumentId($entity)
        );
    }

    public function getDocumentId(object $entity): string
    {
        return $this->getMapper($entity)->getId($entity);
    }

    public function updatePage(object $entity, int $pageNr, string $content): void
    {
        $mapper = $this->getMapper($entity);

        $this->elasticService->updatePage(
            $mapper->getId($entity),
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
