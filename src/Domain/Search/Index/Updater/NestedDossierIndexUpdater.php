<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Updater;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Index\Schema\ElasticPath;
use Shared\Service\Elastic\ElasticClientInterface;

readonly class NestedDossierIndexUpdater
{
    use RetryIndexUpdaterTrait;

    public function __construct(
        private ElasticClientInterface $elastic,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $dossierDoc
     *
     * @throws ClientResponseException
     */
    public function update(AbstractDossier $dossier, array $dossierDoc): void
    {
        $this->retry(fn: function () use ($dossier, $dossierDoc) {
            $this->elastic->updateByQuery([
                'index' => ElasticConfig::WRITE_INDEX,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['terms' => [ElasticField::TYPE->value => ElasticDocumentType::getSubTypeValues()]],
                                ['nested' => [
                                    'path' => ElasticNestedField::DOSSIERS->value,
                                    'query' => [
                                        'term' => [
                                            ElasticPath::dossiersId()->value => ElasticDocumentId::forDossier($dossier)],
                                    ],
                                ]],
                            ],
                        ],
                    ],
                    'script' => [
                        'source' => <<< EOF
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].id == params.dossier.id) {
                                    ctx._source.dossiers[i] = params.dossier;
                                }
                            }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            'dossier' => $dossierDoc,
                        ],
                    ],
                ],
            ]);
        });
    }
}
