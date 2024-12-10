<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticField;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Query\Aggregation;
use App\Service\Search\Query\Query;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Erichard\ElasticQueryBuilder\Aggregation\NestedAggregation;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Jaytaph\TypeArray\TypeArray;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class RolloverCounter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ElasticClientInterface $elasticClient,
        private DossierTypeManager $dossierTypeManager,
    ) {
    }

    /**
     * @return array<array-key, MainTypeCount>
     */
    public function getEntityCounts(ElasticIndexDetails $index): array
    {
        $counts = [];

        $elasticCounts = $this->getElasticCounts($index);
        $databaseCounts = $this->getDatabaseCounts();

        foreach ($this->dossierTypeManager->getAllConfigs() as $typeConfig) {
            $type = ElasticDocumentType::fromEntityClass($typeConfig->getEntityClass());
            /** @var string $typeKey */
            $typeKey = $type->value;

            $subCounts = [];
            foreach ($typeConfig->getSubEntityClasses() as $subTypeClass) {
                $subType = ElasticDocumentType::fromEntityClass($subTypeClass);
                /** @var string $subTypeKey */
                $subTypeKey = $subType->value;
                $subCounts[] = new SubtypeCount(
                    $subType,
                    $databaseCounts[$typeKey]['subtypes'][$subTypeKey]['count'] ?? 0,
                    $elasticCounts[$typeKey]['subtypes'][$subTypeKey]['count'] ?? 0,
                    $databaseCounts[$typeKey]['subtypes'][$subTypeKey]['pages'] ?? 0,
                    $elasticCounts[$typeKey]['subtypes'][$subTypeKey]['pages'] ?? 0,
                );
            }

            $counts[] = new MainTypeCount(
                $type,
                $databaseCounts[$typeKey]['count'] ?? 0,
                $elasticCounts[$typeKey]['count'] ?? 0,
                $subCounts,
            );
        }

        return $counts;
    }

    /**
     * @return array<string, array{count: int, subtypes?: non-empty-array<string, array{count: int, pages: int}>}>
     */
    private function getElasticCounts(ElasticIndexDetails $index): array
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setIndex($index->name);
        $queryBuilder->setSize(0);

        $queryBuilder->addAggregation(
            Aggregation::terms(
                name: ElasticField::TOPLEVEL_TYPE->value,
                fieldOrSource: ElasticField::TOPLEVEL_TYPE->value,
                aggregations: [
                    Aggregation::filter(
                        name: 'main_types_only',
                        query: Query::Terms(
                            field: 'type',
                            values: ElasticDocumentType::getMainTypeValues(),
                        ),
                    ),
                    Aggregation::terms(
                        name: ElasticField::SUBLEVEL_TYPE->value,
                        fieldOrSource: ElasticField::SUBLEVEL_TYPE->value,
                        aggregations: [
                            new NestedAggregation(
                                'pages',
                                'pages',
                            ),
                        ]
                    ),
                ],
            ),
        );

        /**
         * @var Elasticsearch $response
         */
        $response = $this->elasticClient->search($queryBuilder->build());
        $typedResponse = new TypeArray($response->asArray());

        $counts = [];
        foreach ($typedResponse->getIterable('[aggregations][toplevel_type][buckets]') as $dossierTypeCount) {
            /** @var string $dossierTypeKey */
            $dossierTypeKey = $dossierTypeCount->getString('[key]');
            $counts[$dossierTypeKey]['count'] = $dossierTypeCount->getInt('[main_types_only][doc_count]');

            foreach ($dossierTypeCount->getIterable('[sublevel_type][buckets]') as $subTypeCount) {
                /** @var string $subTypeKey */
                $subTypeKey = $subTypeCount->getString('[key]');
                $counts[$dossierTypeKey]['subtypes'][$subTypeKey]['count'] = $subTypeCount->getInt('[doc_count]');
                $counts[$dossierTypeKey]['subtypes'][$subTypeKey]['pages'] = $subTypeCount->getInt('[pages][doc_count]');
            }
        }

        return $counts;
    }

    /**
     * @return array<string, array{count: int, subtypes?: non-empty-array<string, array{count: int, pages: int}>}>
     */
    private function getDatabaseCounts(): array
    {
        $counts = [];

        foreach ($this->dossierTypeManager->getAllConfigs() as $typeConfig) {
            $entityClass = $typeConfig->getEntityClass();
            $type = ElasticDocumentType::fromEntityClass($entityClass);
            /** @var string $typeKey */
            $typeKey = $type->value;
            $repository = $this->entityManager->getRepository($entityClass);
            $typeCount = $repository->count([]);

            $counts[$typeKey] = [
                'count' => $typeCount,
            ];

            if ($typeCount === 0) {
                continue;
            }

            foreach ($typeConfig->getSubEntityClasses() as $subTypeClass) {
                $subType = ElasticDocumentType::fromEntityClass($subTypeClass);
                /** @var string $subTypeKey */
                $subTypeKey = $subType->value;
                $counts[$typeKey]['subtypes'][$subTypeKey] = $this->getSubtypeDatabaseCounts($subTypeClass);
            }
        }

        return $counts;
    }

    /**
     * @param class-string $class
     *
     * @return array{count: int, pages: int}
     */
    private function getSubtypeDatabaseCounts(string $class): array
    {
        $repository = $this->entityManager->getRepository($class);

        /**
         * @var array{count: int, pages: int}
         */
        return $repository->createQueryBuilder('e')
            ->select('COUNT(e.id) as count')
            ->addSelect('SUM(e.fileInfo.pageCount) as pages')
            ->getQuery()->getSingleResult();
    }
}
