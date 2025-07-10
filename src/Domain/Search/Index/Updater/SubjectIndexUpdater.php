<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Updater;

use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Service\Elastic\ElasticClientInterface;

readonly class SubjectIndexUpdater
{
    public function __construct(
        private ElasticClientInterface $elastic,
    ) {
    }

    public function update(Subject $subject): void
    {
        $this->elastic->updateByQuery([
            'index' => ElasticConfig::WRITE_INDEX,
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
                        'minimum_should_match' => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.subject !== null && ctx._source.subject.id.equals(params.subject.id)) {
                            ctx._source.subject = params.subject;
                        }

                        if (ctx._source.dossiers != null) {
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].subject.id.equals(params.subject.id)) {
                                    ctx._source.dossiers[i].subject = params.subject;
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        'subject' => [
                            'name' => $subject->getName(),
                            'id' => $subject->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
