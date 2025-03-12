<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Updater;

use App\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\ElasticConfig;
use App\Entity\Department;
use App\Service\Elastic\ElasticClientInterface;

readonly class DepartmentIndexUpdater
{
    public function __construct(
        private ElasticClientInterface $elastic,
    ) {
    }

    public function update(Department $department): void
    {
        $this->elastic->updateByQuery([
            'index' => ElasticConfig::WRITE_INDEX,
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
                                        ElasticPath::dossiersDepartmentsId()->value,
                                    ],
                                ],
                            ]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.departments != null) {
                            for (int i = 0; i < ctx._source.departments.length; i++) {
                                if (ctx._source.departments[i].id.equals(params.department.id)) {
                                    ctx._source.departments[i] = params.department;
                                }
                            }
                        }

                        if (ctx._source.dossiers != null) {
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].departments != null) {
                                    for (int j = 0; j < ctx._source.dossiers[i].departments.length; j++) {
                                        if (ctx._source.dossiers[i].departments[j].id.equals(params.department.id)) {
                                            ctx._source.dossiers[i].departments[j] = params.department;
                                        }
                                    }
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        'department' => [
                            'name' => DepartmentFieldMapper::fromDepartment($department)->getIndexValue(),
                            'id' => $department->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
