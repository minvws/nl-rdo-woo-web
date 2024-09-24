<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Updater;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocumentType;
use App\ElasticConfig;
use App\Service\Elastic\ElasticClientInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Psr\Log\LoggerInterface;

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
        $this->retry(function () use ($dossier, $dossierDoc) {
            $this->elastic->updateByQuery([
                'index' => ElasticConfig::WRITE_INDEX,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['terms' => ['type' => ElasticDocumentType::getSubTypeValues()]],
                                ['match' => ['dossier_nr' => $dossier->getDossierNr()]],
                            ],
                        ],
                    ],
                    'script' => [
                        'source' => <<< EOF
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].dossier_nr == params.dossier.dossier_nr) {
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
