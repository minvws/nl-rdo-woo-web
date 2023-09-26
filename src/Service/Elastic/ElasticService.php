<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\ElasticConfig;
use App\Entity\Department;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\GovernmentOfficial;
use App\Service\DateRangeConverter;
use App\Service\Search\Model\Config;
use App\Service\Worker\Audio\Metadata;
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

    protected ElasticClientInterface $elastic;
    protected LoggerInterface $logger;

    public function __construct(ElasticClientInterface $elastic, LoggerInterface $logger)
    {
        $this->elastic = $elastic;
        $this->logger = $logger;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string[]               $metadata
     * @param array<int, mixed>|null $pages
     */
    public function updateDocument(Document $document, array $metadata = null, array $pages = null): void
    {
        [$dossiers, $dossierIds] = $this->dossiersAsArray($document);

        $inquiryIds = [];
        foreach ($document->getInquiries() as $inquiry) {
            $inquiryIds[] = $inquiry->getId();
        }

        $file = $document->getFileInfo();
        $documentDoc = [
            'type' => 'document',
            'document_nr' => $document->getDocumentNr(),
            'dossier_nr' => $dossierIds,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getType(),
            'source_type' => $file->getSourceType(),
            'date' => $document->getDocumentDate()->format(\DateTimeInterface::ATOM),
            'filename' => $file->getName(),
            'family_id' => $document->getFamilyId() ?? 0,
            'document_id' => $document->getDocumentId() ?? 0,
            'thread_id' => $document->getThreadId() ?? 0,
            'judgement' => $document->getJudgement(),
            'grounds' => $document->getGrounds(),
            'subjects' => $document->getSubjects(),
            'date_period' => $document->getPeriod(),
            'audio_duration' => $document->getDuration(),
            'document_pages' => $document->getPageCount(),
            'dossiers' => $dossiers,
            'inquiry_ids' => $inquiryIds,
        ];

        if ($metadata !== null) {
            $documentDoc['metadata'] = $metadata;
        }

        if ($pages !== null) {
            $documentDoc['pages'] = $pages;
        }

        // Update main document
        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getDocumentNr(),
            'body' => [
                'doc' => $documentDoc,
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

    public function updateDossier(Dossier $dossier, bool $updateDocuments = true): void
    {
        list($departments, $officials) = $this->getDepartmentsAndOfficials($dossier);

        $inquiryIds = [];
        foreach ($dossier->getInquiries() as $inquiry) {
            $inquiryIds[] = $inquiry->getId();
        }

        $dossierDoc = [
            'type' => 'dossier',
            'dossier_nr' => $dossier->getDossierNr(),
            'title' => $dossier->getTitle(),
            'status' => $dossier->getStatus(),
            'summary' => $dossier->getSummary(),
            'document_prefix' => $dossier->getDocumentPrefix(),
            'departments' => $departments,
            'government_officials' => $officials,
            'date_from' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
            'date_to' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
            'date_range' => [
                'gte' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                'lte' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
            ],
            'date_period' => DateRangeConverter::convertToString($dossier->getDateFrom(), $dossier->getDateTo()),
            'publication_reason' => $dossier->getPublicationReason(),
            'decision' => $dossier->getDecision(),
            'inquiry_ids' => $inquiryIds,
        ];

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

    public function updateAudio(Document $document, Metadata $metadata): void
    {
        $ids = [];
        foreach ($document->getDossiers() as $dossier) {
            $ids[] = $dossier->getId();
        }

        $body = [
            'type' => 'audio',
            'dossier_nr' => $ids,
            'document_nr' => $document->getDocumentNr(),
            'duration' => $metadata->getDuration(),
            'sample_rate' => $metadata->getSampleRate(),
            'channels' => $metadata->getChannels(),
            'bit_rate' => $metadata->getBitRate(),
            'format' => $metadata->getFormat(),
        ];

        $this->elastic->update([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $document->getDocumentNr(),
            'body' => [
                'doc' => $body,
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

    /**
     * @return mixed[]
     */
    protected function dossiersAsArray(Document $document): array
    {
        $dossiers = [];
        $dossierIds = [];
        foreach ($document->getDossiers() as $dossier) {
            list($departments, $officials) = $this->getDepartmentsAndOfficials($dossier);

            $inquiryIds = [];
            foreach ($document->getInquiries() as $inquiry) {
                $inquiryIds[] = $inquiry->getId();
            }

            $data = [
                'type' => 'dossier',
                'dossier_nr' => $dossier->getDossierNr(),
                'title' => $dossier->getTitle(),
                'summary' => $dossier->getSummary(),
                'status' => $dossier->getStatus(),
                'document_prefix' => $dossier->getDocumentPrefix(),
                'departments' => $departments,
                'government_officials' => $officials,
                'date_from' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                'date_to' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                'date_range' => [
                    'gte' => $dossier->getDateFrom()?->format(\DateTimeInterface::ATOM),
                    'lte' => $dossier->getDateTo()?->format(\DateTimeInterface::ATOM),
                ],
                'date_period' => DateRangeConverter::convertToString($dossier->getDateFrom(), $dossier->getDateTo()),
                'publication_reason' => $dossier->getPublicationReason(),
                'decision' => $dossier->getDecision(),
                'inquiry_ids' => $inquiryIds,
            ];

            $dossiers[] = $data;
            $dossierIds[] = $dossier->getDossierNr();
        }

        return [$dossiers, $dossierIds];
    }

    public function updateOfficial(GovernmentOfficial $old, GovernmentOfficial $new): void
    {
        $this->elastic->updateByQuery([
            'index' => ElasticConfig::WRITE_INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => ['government_official.id' => $old->getId()]],
                            ['match' => ['dossiers.government_official.id' => $old->getId()]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.government_official != null) {
                            for (int i = 0; i < ctx._source.government_official.length; i++) {
                                if (ctx._source.government_official[i].id.equals(params.old.id)) {
                                    ctx._source.government_official[i] = params.new;
                                }
                            }
                        }

                        if (ctx._source.dossiers != null) {
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].government_official != null) {
                                    for (int j = 0; j < ctx._source.dossiers[i].government_official.length; j++) {
                                        if (ctx._source.dossiers[i].government_official[j].id.equals(params.old.id)) {
                                            ctx._source.dossiers[i].government_official[j] = params.new;
                                        }
                                    }
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        'old' => [
                            'name' => $old->getName(),
                            'id' => $old->getId(),
                        ],
                        'new' => [
                            'name' => $new->getName(),
                            'id' => $new->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function updateDepartment(Department $old, Department $new): void
    {
        $this->elastic->updateByQuery([
            'index' => ElasticConfig::WRITE_INDEX,
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => ['departments.id' => $old->getId()]],
                            ['match' => ['dossiers.departments.id' => $old->getId()]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ],
                'script' => [
                    'source' => <<< EOF
                        if (ctx._source.departments != null) {
                            for (int i = 0; i < ctx._source.departments.length; i++) {
                                if (ctx._source.departments[i].id.equals(params.old.id)) {
                                    ctx._source.departments[i] = params.new;
                                }
                            }
                        }

                        if (ctx._source.dossiers != null) {
                            for (int i = 0; i < ctx._source.dossiers.length; i++) {
                                if (ctx._source.dossiers[i].departments != null) {
                                    for (int j = 0; j < ctx._source.dossiers[i].departments.length; j++) {
                                        if (ctx._source.dossiers[i].departments[j].id.equals(params.old.id)) {
                                            ctx._source.dossiers[i].departments[j] = params.new;
                                        }
                                    }
                                }
                            }
                        }
EOF,
                    'lang' => 'painless',
                    'params' => [
                        'old' => [
                            'name' => $old->getName(),
                            'id' => $old->getId(),
                        ],
                        'new' => [
                            'name' => $new->getName(),
                            'id' => $new->getId(),
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return mixed[]
     */
    protected function getDepartmentsAndOfficials(Dossier $dossier): array
    {
        $departments = [];
        foreach ($dossier->getDepartments() as $department) {
            $departments[] = [
                'name' => $department->getName(),
                'id' => $department->getId(),
            ];
        }

        $officials = [];
        foreach ($dossier->getGovernmentOfficials() as $official) {
            $officials[] = [
                'name' => $official->getName(),
                'id' => $official->getId(),
            ];
        }

        return [$departments, $officials];
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
    private function updateAllDocumentsForDossier(Dossier $dossier, array $dossierDoc): void
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

    // Removes the nested dossier entry from all documents that have this dossier. Note that this can
    // leave orphaned documents (documents that do not have any dossiers as nested entities). We assume
    // that these are cleaned up BEFORE running this function.
    private function removeAllDocumentsForDossier(Dossier $dossier): void
    {
        $this->retry(function () use ($dossier) {
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
                            for (int i = ctx._source.dossiers.length-1; i>=0; i-) {
                                if (ctx._source.dossiers[i].dossier_nr == params.dossier_nr) {
                                    ctx._source.dossiers.remove(i);
                                }
                            }
EOF,
                        'lang' => 'painless',
                        'params' => [
                            'dossier_nr' => $dossier->getDossierNr(),
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
    public function removeDossier(Dossier $dossier): void
    {
        // Remove all dossier entries found in documents
        $this->removeAllDocumentsForDossier($dossier);

        // Delete dossier document
        $this->elastic->delete([
            'index' => ElasticConfig::WRITE_INDEX,
            'id' => $dossier->getDossierNr(),
        ]);
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
