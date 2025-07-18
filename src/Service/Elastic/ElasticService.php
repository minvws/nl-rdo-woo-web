<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentId;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use MinVWS\TypeArray\TypeArray;
use Psr\Log\LoggerInterface;

/**
 * Service for interacting with Elasticsearch. Together with the SearchService, this should be the only entrypoint to
 * elasticsearch.
 */
class ElasticService
{
    public function __construct(
        private readonly ElasticClientInterface $elastic,
        private LoggerInterface $logger,
    ) {
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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
                'id' => ElasticDocumentId::forDossier($dossier),
            ]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() === 404) {
                return; // Dossier was not in the index (already deleted?) that's ok
            }

            throw $exception;
        }
    }
}
