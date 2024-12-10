<?php

declare(strict_types=1);

namespace App\Service\Search\Object;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\ElasticConfig;
use App\Service\Elastic\ElasticClientInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Jaytaph\TypeArray\TypeArray;

/**
 * DocumentHandler would be a better Elasticsearch name
 * But that is really confusing because we also have Documents.
 */
class ObjectHandler
{
    protected ElasticClientInterface $elastic;

    public function __construct(ElasticClientInterface $elastic)
    {
        $this->elastic = $elastic;
    }

    /**
     * Returns true when the given document is ingested in ElasticSearch.
     */
    public function isIngested(Document $document): bool
    {
        $response = $this->elastic->exists([
            'index' => ElasticConfig::READ_INDEX,
            'id' => $document->getDocumentNr(),
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
                                    '_id' => $document->getDocumentNr(),
                                ],
                            ],
                            [
                                'nested' => [
                                    'path' => 'pages',
                                    'query' => [
                                        'term' => [
                                            'pages.page_nr' => $pageNr,
                                        ],
                                    ],
                                    'inner_hits' => [
                                        '_source' => 'pages.content',
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

            $content = $response->getString('[hits][hits][0][inner_hits][pages][hits][hits][0][_source][content]', '');

            return $content;
        } catch (\Throwable $e) {
            return '';
        }
    }
}
