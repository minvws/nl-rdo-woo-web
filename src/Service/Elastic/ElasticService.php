<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\ElasticConfig;
use App\Entity\Department;
use App\Entity\Dossier;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with Elasticsearch. Together with the SearchService, this should be the only entrypoint to elasticsearch.
 */
class ElasticService
{
    private const MAX_RETRIES = 10;

    public function __construct(
        private readonly ElasticClientInterface $elastic,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function updatePage(string $id, int $pageNr, string $content): void
    {
        $this->logger->debug('[Elasticsearch] Updating page');
        $this->retry(function () use ($id, $pageNr, $content) {
            $this->elastic->update([
                'index' => ElasticConfig::WRITE_INDEX,
                'id' => $id,
                'body' => [
                    'script' => [
                        'source' => <<< EOF
                                if (ctx._source.pages == null) {
                                    ctx._source.pages = [params.page];
                                } else {
                                    boolean found = false;
                                    for (int i = 0; i < ctx._source.pages.length; ++i) {
                                        if (ctx._source.pages[i].page_nr == params.page.page_nr) {
                                            ctx._source.pages[i] = params.page;
                                            found = true;
                                            break;
                                        }
                                    }
                                    if (found == false) {
                                        ctx._source.pages.add(params.page);
                                    }
                                }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            'page' => [
                                'page_nr' => $pageNr,
                                'content' => $content,
                            ],
                        ],
                    ],
                ],
            ]);
        });
    }

    public function updateDocument(ElasticDocument $document): void
    {
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getId(),
            'body' => [
                'doc' => $document->getDocumentValues(),
                'doc_as_upsert' => true,
            ],
        ]);
    }

    /**
     * @deprecated Use updateDoc instead. This method is to be phased out as part of woo-2705.
     */
    public function updateDossierDecisionContent(Dossier $dossier, string $content): void
    {
        $dossierDoc = [
            'decision_content' => $content,
        ];

        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $dossier->getDossierNr(),
            'body' => [
                'doc' => $dossierDoc,
                'doc_as_upsert' => true,
            ],
        ]);
    }

    private function documentExists(string $id): bool
    {
        $result = $this->elastic->exists([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ]);

        /** @var Elasticsearch $result */
        return $result->asBool();
    }

    public function getDocument(string $id): TypeArray
    {
        $result = $this->elastic->get([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ]);

        /** @var Elasticsearch $result */
        return new TypeArray($result->asArray());
    }

    public function updateDepartment(Department $department): void
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
                            'name' => $department->getName(),
                            'id' => $department->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param array<string, mixed> $dossierDoc
     *
     * @throws ClientResponseException
     */
    public function updateAllDocumentsForDossier(AbstractDossier $dossier, array $dossierDoc): void
    {
        $this->logger->debug('[Elasticsearch] Updating nested dossiers');

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

    public function removeDocument(string $id): void
    {
        if (! $this->documentExists($id)) {
            return;
        }

        // @Note: it's possible that the document is removed in between checking for existence and deleting.
        $this->elastic->delete([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
        ]);
    }

    // Removes a dossier and all references inside documents that have this dossier as nested object.
    public function removeDossier(AbstractDossier $dossier): void
    {
        try {
            // Delete dossier document
            $this->elastic->delete([
                'index' => ElasticConfig::WRITE_INDEX,
                'id' => $dossier->getDossierNr(),
            ]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() === 404) {
                return; // Dossier was not in the index (already deleted?) that's ok
            }

            throw $exception;
        }
    }

    // Will retry a callable for a specified number of times. If the callable throws a ClientResponseException with a 409 code, it will
    // retry the callable. If the callable throws a ClientResponseException with a different code, it will throw the exception.
    // If the callable throws any other exception, it will throw the exception.
    private function retry(callable $fn): void
    {
        for ($retryCount = 0; $retryCount <= self::MAX_RETRIES; $retryCount++) {
            try {
                $fn();

                return;
            } catch (ClientResponseException $e) {
                if ($retryCount === self::MAX_RETRIES) {
                    $this->logger->error('[Elasticsearch] Too many retries', [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw $e;
                }
                if ($e->getCode() != 409) {
                    $this->logger->error('[Elasticsearch] An error occurred: {message}', [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    throw $e;
                }

                $waitMs = (int) ceil(min(100000 * pow(1.4, $retryCount), 5000000));
                $this->logger->notice('[Elasticsearch] Update dossier version mismatch. Retrying...', [
                    'waitMs' => $waitMs,
                ]);
                usleep($waitMs);
            }
        }
    }
}
