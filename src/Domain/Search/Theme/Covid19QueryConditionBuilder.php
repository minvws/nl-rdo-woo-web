<?php

declare(strict_types=1);

namespace App\Domain\Search\Theme;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use App\Repository\OrganisationRepository;
use App\Service\Search\Query\Condition\QueryConditionBuilderInterface;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class Covid19QueryConditionBuilder implements QueryConditionBuilderInterface
{
    public const string ORGANISATION = 'Directie Open Overheid';

    public function __construct(
        private OrganisationRepository $organisationRepository,
    ) {
    }

    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        $this->addFilterForWooDecisionWithSubtypes($query);
        $this->addFilterForSubjects($query);
    }

    private function addFilterForWooDecisionWithSubtypes(BoolQuery $query): void
    {
        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::nested(
                                path: ElasticNestedField::DOSSIERS->value,
                                query: Query::term(
                                    field: ElasticPath::dossiersType()->value,
                                    value: ElasticDocumentType::WOO_DECISION->value,
                                ),
                            ),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::term(
                                field: ElasticField::TYPE->value,
                                value: ElasticDocumentType::WOO_DECISION->value,
                            ),
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }

    private function addFilterForSubjects(BoolQuery $query): void
    {
        $subjectIds = $this->getSubjectIds();

        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::nested(
                                path: ElasticNestedField::DOSSIERS->value,
                                query: Query::terms(
                                    field: ElasticPath::dossiersSubjectId()->value,
                                    values: $subjectIds,
                                ),
                            ),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: ElasticPath::subjectId()->value,
                                values: $subjectIds,
                            ),
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }

    /**
     * @return array<array-key, string>
     */
    private function getSubjectIds(): array
    {
        $organisation = $this->organisationRepository->findOneBy(['name' => self::ORGANISATION]);
        if ($organisation === null) {
            throw new \RuntimeException('Covid-19 theme cannot find the organisation');
        }

        return $organisation
            ->getSubjects()
            ->filter(
                static fn (Subject $subject): bool => in_array(
                    $subject->getName(),
                    Covid19Subject::values(),
                )
            )->map(
                static fn (mixed $subject) => $subject->getId()->toRfc4122()
            )->toArray();
    }
}
