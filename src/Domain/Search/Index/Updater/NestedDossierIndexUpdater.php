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

    private const string PARAMETER_NAME = 'dossier';

    public function __construct(
        private ElasticClientInterface $elastic,
        private LoggerInterface $logger,
        private ElasticConfig $elasticConfig,
    ) {
    }

    /**
     * @param array<string, mixed> $dossierDoc
     *
     * @throws ClientResponseException
     */
    public function update(AbstractDossier $dossier, array $dossierDoc): void
    {
        $dossiersField = ElasticNestedField::DOSSIERS->value;
        $idField = ElasticField::ID->value;
        $parameterName = self::PARAMETER_NAME;

        $this->retry(fn: function () use ($dossier, $dossierDoc, $dossiersField, $idField, $parameterName) {
            $this->elastic->updateByQuery([
                'index' => $this->elasticConfig->writeIndex,
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
                            for (int i = 0; i < ctx._source.{$dossiersField}.length; i++) {
                                if (ctx._source.{$dossiersField}[i].{$idField} == params.{$parameterName}.{$idField}) {
                                    ctx._source.{$dossiersField}[i] = params.{$parameterName};
                                }
                            }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            $parameterName => $dossierDoc,
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
