<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\WooDecision\DocumentMapper;
use App\Domain\Search\Index\WooDecision\WooDecisionMapper;
use App\ElasticConfig;
use App\Entity\Department;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\Search\Model\Config;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with Elasticsearch. Together with the SearchService, this should be the only entrypoint to elasticsearch.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ElasticService
{
    protected static int $maxRetries = 10;

    public function __construct(
        private readonly ElasticClientInterface $elastic,
        private LoggerInterface $logger,
        private readonly WooDecisionMapper $wooDecisionMapper,
        private readonly DocumentMapper $documentMapper,
    ) {
    }

    /**
     * @throws \Elastic\Elasticsearch\Exception\ServerResponseException
     */
    public function updatePage(Document $document, int $pageNr, string $content): void
    {
        $this->logger->debug('[Elasticsearch][Index Page] Inserting page');
        $this->retry(function () use ($document, $pageNr, $content) {
            $this->elastic->update([
                'index' => ElasticConfig::WRITE_INDEX,
                'id' => $document->getDocumentNr(),
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

    /**
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function updateDocument(Document $document, ?array $metadata = null, ?array $pages = null): void
    {
        // Update main document
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getDocumentNr(),
            'body' => [
                'doc' => $this->documentMapper->map($document, $metadata, $pages)->getDocumentValues(),
                'doc_as_upsert' => true,
            ],
        ]);
    }

    /**
     * @param array<int, string> $pages
     */
    public function setPages(Document $document, array $pages): void
    {
        $pageDocs = [];
        foreach ($pages as $pageNr => $content) {
            $pageDocs[] = [
                'page_nr' => $pageNr,
                'content' => $content,
            ];
        }

        $documentDoc = [
            'pages' => $pageDocs,
        ];

        // Update main document
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getDocumentNr(),
            'body' => [
                'doc' => $documentDoc,
            ],
        ]);
    }

    /**
     * @deprecated use DossierIndexer::index()
     */
    public function updateDossier(Dossier $dossier, bool $updateDocuments = true): void
    {
        $dossierDoc = $this->wooDecisionMapper->map($dossier)->getDocumentValues();

        // Update main dossier document
        $this->logger->debug('[Elasticsearch][Update Dossier] Updating dossier');
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $dossier->getDossierNr(),
            'body' => [
                'doc' => $dossierDoc,
                'doc_as_upsert' => true,
            ],
        ]);

        if ($updateDocuments === true) {
            $this->updateAllDocumentsForDossier($dossier, $dossierDoc);
        }
    }

    public function updateDoc(string $id, ElasticDocument $doc): void
    {
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $id,
            'body' => [
                'doc' => $doc->getDocumentValues(),
                'doc_as_upsert' => true,
            ],
        ]);
    }

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

    public function documentExists(string $documentNr): bool
    {
        $result = $this->elastic->exists([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $documentNr,
        ]);

        /** @var Elasticsearch $result */
        return $result->asBool();
    }

    public function getDocument(Document $document): TypeArray
    {
        $result = $this->elastic->get([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getDocumentNr(),
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
        $this->logger->debug('[Elasticsearch][Update Dossier] Updating dossier in document');
        $this->retry(function () use ($dossier, $dossierDoc) {
            $this->elastic->updateByQuery([
                'index' => ElasticConfig::WRITE_INDEX,
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['type' => Config::TYPE_DOCUMENT]],
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

    // Removes a given document
    public function removeDocument(string $documentNr): void
    {
        if (! $this->documentExists($documentNr)) {
            return;
        }

        // @Note: it's possible that the document is removed in between checking for existence and deleting.

        // Delete document
        $this->elastic->delete([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $documentNr,
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
    protected function retry(callable $fn): void
    {
        for ($retryCount = 0; $retryCount <= self::$maxRetries; $retryCount++) {
            try {
                $fn();

                return;
            } catch (ClientResponseException $e) {
                if ($retryCount == self::$maxRetries) {
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
