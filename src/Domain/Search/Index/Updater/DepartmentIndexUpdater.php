<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Updater;

use App\Domain\Search\Result\FacetValue\AbbreviatedValue;
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
                            ['match' => ['departments.id' => $department->getId()]],
                            ['nested' => [
                                'path' => 'dossiers',
                                'query' => [
                                    'term' => ['dossiers.departments.id' => $department->getId()],
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
                            'name' => AbbreviatedValue::fromDepartment($department)->getIndexValue(),
                            'id' => $department->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
