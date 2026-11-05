<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Updater;

use Shared\Domain\Department\Department;
use Shared\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Index\Schema\ElasticObjectField;
use Shared\Domain\Search\Index\Schema\ElasticPath;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Service\Search\Query\Dsl\QueryParameter;

readonly class DepartmentIndexUpdater
{
    private const string PARAMETER_NAME = 'department';

    public function __construct(
        private ElasticClientInterface $elastic,
        private ElasticConfig $elasticConfig,
    ) {
    }

    public function update(Department $department): void
    {
        $departmentsField = ElasticObjectField::DEPARTMENTS->value;
        $dossiersField = ElasticNestedField::DOSSIERS->value;
        $idField = ElasticField::ID->value;
        $parameterName = self::PARAMETER_NAME;
        $parameterIdPath = "params.{$parameterName}.{$idField}";

        $this->elastic->updateByQuery([
            'index' => $this->elasticConfig->writeIndex,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => [
                                ElasticPath::departmentsId()->value => $department->getId(),
                            ]],
                            ['nested' => [
                                'path' => ElasticNestedField::DOSSIERS->value,
                                'query' => [
                                    'term' => [
                                        ElasticPath::dossiersDepartmentsId()->value => $department->getId(),
                                    ],
                                ],
                            ]],
                        ],
                        QueryParameter::MINIMUM_SHOULD_MATCH => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.{$departmentsField} != null) {
                            for (int i = 0; i < ctx._source.{$departmentsField}.length; i++) {
                                if (ctx._source.{$departmentsField}[i].{$idField}.equals({$parameterIdPath})) {
                                    ctx._source.{$departmentsField}[i] = params.{$parameterName};
                                }
                            }
                        }

                        if (ctx._source.{$dossiersField} != null) {
                            for (int i = 0; i < ctx._source.{$dossiersField}.length; i++) {
                                if (ctx._source.{$dossiersField}[i].{$departmentsField} != null) {
                                    for (int j = 0; j < ctx._source.{$dossiersField}[i].{$departmentsField}.length; j++) {
                                        if (ctx._source.{$dossiersField}[i].{$departmentsField}[j].{$idField}.equals({$parameterIdPath})) {
                                            ctx._source.{$dossiersField}[i].{$departmentsField}[j] = params.{$parameterName};
                                        }
                                    }
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        $parameterName => [
                            ElasticField::NAME->value => DepartmentFieldMapper::fromDepartment($department)->getIndexValue(),
                            ElasticField::ID->value => $department->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
