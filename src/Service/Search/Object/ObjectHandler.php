<?php

declare(strict_types=1);

namespace App\Service\Search\Object;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Index\Schema\ElasticPath;
use App\Service\Elastic\ElasticClientInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;
use MinVWS\TypeArray\TypeArray;

/**
 * DocumentHandler would be a better Elasticsearch name
 * But that is really confusing because we also have Documents.
 */
readonly class ObjectHandler
{
    public function __construct(
        private ElasticClientInterface $elastic,
    ) {
    }

    /**
     * Returns true when the given document is ingested in ElasticSearch.
     */
    public function isIngested(Document $document): bool
    {
        $response = $this->elastic->exists([
            'index' => ElasticConfig::READ_INDEX,
            'id' => ElasticDocumentId::forObject($document),
        ]);

        /** @var Elasticsearch $response */
        return $response->asBool();
    }

    /**
     * Returns the explicit content from a given document / page number.
     */
    public function getPageContent(Document $document, int $pageNr): string
    {
        $params = [
            'index' => ElasticConfig::READ_INDEX,
            'body' => [
                '_source' => false,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    '_id' => ElasticDocumentId::forObject($document),
                                ],
                            ],
                            [
                                'nested' => [
                                    'path' => ElasticNestedField::PAGES->value,
                                    'query' => [
                                        'term' => [
                                            ElasticPath::pagesPageNr()->value => $pageNr,
                                        ],
                                    ],
                                    'inner_hits' => [
                                        '_source' => ElasticPath::pagesContent()->value,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            /** @var Elasticsearch $response */
            $response = $this->elastic->search($params);
            $response = new TypeArray($response->asArray());

            return $response->getString('[hits][hits][0][inner_hits][pages][hits][hits][0][_source][content]', '');
        } catch (\Throwable) {
            return '';
        }
    }
}
