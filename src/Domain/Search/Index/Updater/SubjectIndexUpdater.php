<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Updater;

use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Index\Schema\ElasticObjectField;
use Shared\Domain\Search\Index\Schema\ElasticPath;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Service\Search\Query\Dsl\QueryParameter;

readonly class SubjectIndexUpdater
{
    public function __construct(
        private ElasticClientInterface $elastic,
        private ElasticConfig $elasticConfig,
    ) {
    }

    public function update(Subject $subject): void
    {
        $subjectField = ElasticObjectField::SUBJECT->value;
        $dossiersField = ElasticNestedField::DOSSIERS->value;
        $idField = ElasticField::ID->value;
        $subjectParameterIdPath = "params.{$subjectField}.{$idField}";

        $this->elastic->updateByQuery([
            'index' => $this->elasticConfig->writeIndex,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => [
                                ElasticPath::subjectId()->value => $subject->getId()]],
                            ['nested' => [
                                'path' => ElasticNestedField::DOSSIERS->value,
                                'query' => [
                                    'term' => [
                                        ElasticPath::dossiersSubjectId()->value => $subject->getId(),
                                    ],
                                ],
                            ]],
                        ],
                        QueryParameter::MINIMUM_SHOULD_MATCH => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.{$subjectField} !== null && ctx._source.{$subjectField}.{$idField}.equals({$subjectParameterIdPath})) {
                            ctx._source.{$subjectField} = params.{$subjectField};
                        }

                        if (ctx._source.{$dossiersField} != null) {
                            for (int i = 0; i < ctx._source.{$dossiersField}.length; i++) {
                                if (ctx._source.{$dossiersField}[i].{$subjectField}.{$idField}.equals({$subjectParameterIdPath})) {
                                    ctx._source.{$dossiersField}[i].{$subjectField} = params.{$subjectField};
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        $subjectField => [
                            ElasticField::NAME->value => $subject->getName(),
                            ElasticField::ID->value => $subject->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
