<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query;

use App\ElasticConfig;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\AggregationGenerator;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\FacetMappingService;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class QueryGeneratorTest extends MockeryTestCase
{
    private string $index = ElasticConfig::READ_INDEX;
    private QueryGenerator $queryGenerator;

    public function setUp(): void
    {
        $facetMapping = new FacetMappingService();
        $contentAccessConditions = new ContentAccessConditions();
        $facetConditions = new FacetConditions($facetMapping);
        $searchTermConditions = new SearchTermConditions();

        $this->queryGenerator = new QueryGenerator(
            new AggregationGenerator(
                $facetMapping,
                $contentAccessConditions,
                $facetConditions,
                $searchTermConditions
            ),
            $contentAccessConditions,
            $facetConditions,
            $searchTermConditions,
        );
    }

    public function testCreateQueryWithMinimalConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new Config(
                pagination: false,
                aggregations: false,
            )
        );

        $this->assertJsonStringEqualsJsonString(
            <<<END
{
    "body": {
        "_source": {
            "excludes": [
                "content",
                "pages",
                "inquiry_ids",
                "dossiers.inquiry_ids"
            ]
        },
        "query": {
            "bool": {
                "should": [
                    {
                        "match_all": {}
                    }
                ],
                "filter": [
                    {
                        "bool": {
                            "minimum_should_match": 1,
                            "should": [
                                {
                                    "bool": {
                                        "filter": [
                                            {
                                                "term": {
                                                    "type": {
                                                        "value": "document"
                                                    }
                                                }
                                            },
                                            {
                                                "nested": {
                                                    "path": "dossiers",
                                                    "query": {
                                                        "terms": {
                                                            "dossiers.status": [
                                                                "published"
                                                            ]
                                                        }
                                                    }
                                                }
                                            }
                                        ]
                                    }
                                },
                                {
                                    "bool": {
                                        "filter": [
                                            {
                                                "term": {
                                                    "type": {
                                                        "value": "dossier"
                                                    }
                                                }
                                            },
                                            {
                                                "terms": {
                                                    "status": [
                                                        "published"
                                                    ]
                                                }
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    }
                ]
            }
        },
        "aggs": {
            "unique_dossiers": {
                "cardinality": {
                    "field": "dossier_nr"
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr"
                }
            }
        },
        "sort": [
            "_score"
        ]
    },
    "index": "{$this->index}",
    "from": 0,
    "size": 0
}
END,
            json_encode($result->build(), JSON_PRETTY_PRINT)
        );
    }

    public function testCreateQueryWithDossierOnlyConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new Config(
                searchType: Config::TYPE_DOSSIER,
                pagination: false,
                aggregations: false,
            )
        );

        $this->assertJsonStringEqualsJsonString(
            <<<END
{
    "body": {
        "_source": {
            "excludes": [
                "content",
                "pages",
                "inquiry_ids",
                "dossiers.inquiry_ids"
            ]
        },
        "query": {
            "bool": {
                "should": [
                    {
                        "match_all": {}
                    }
                ],
                "filter": [
                    {
                        "bool": {
                            "filter": [
                                {
                                    "term": {
                                        "type": {
                                            "value": "dossier"
                                        }
                                    }
                                },
                                {
                                    "terms": {
                                        "status": [
                                            "published"
                                        ]
                                    }
                                }
                            ]
                        }
                    }
                ]
            }
        },
        "aggs": {
            "unique_dossiers": {
                "cardinality": {
                    "field": "dossier_nr"
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr"
                }
            }
        },
        "sort": [
            "_score"
        ]
    },
    "index": "{$this->index}",
    "from": 0,
    "size": 0
}
END,
            json_encode($result->build(), JSON_PRETTY_PRINT)
        );
    }

    public function testCreateQueryWithComplexConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new Config(
                limit: 15,
                offset: 6,
                query: 'search terms',
                documentInquiries: ['doc-inq-1'],
                dossierInquiries: ['dos-inq-1', 'dos-inq-2'],
                sortField: SortField::DECISION_DATE,
                sortOrder: SortOrder::ASC,
            )
        );

        $this->assertJsonStringEqualsJsonString(
            <<<END
{
    "body": {
        "_source": {
            "excludes": [
                "content",
                "pages",
                "inquiry_ids",
                "dossiers.inquiry_ids"
            ]
        },
        "suggest": {
            "search-input": {
                "text": "search terms",
                "term": {
                    "field": "content_for_suggestions",
                    "size": 3,
                    "sort": "frequency",
                    "suggest_mode": "popular",
                    "string_distance": "jaro_winkler"
                }
            }
        },
        "query": {
            "bool": {
                "minimum_should_match": 1,
                "should": [
                    {
                        "bool": {
                            "minimum_should_match": 1,
                            "should": [
                                {
                                    "nested": {
                                        "path": "dossiers",
                                        "query": {
                                            "bool": {
                                                "minimum_should_match": 1,
                                                "should": [
                                                    {
                                                        "query_string": {
                                                            "query": "search terms",
                                                            "fields": [
                                                                "dossiers.title"
                                                            ],
                                                            "boost": 3
                                                        }
                                                    },
                                                    {
                                                        "query_string": {
                                                            "query": "search terms",
                                                            "fields": [
                                                                "dossiers.summary"
                                                            ],
                                                            "boost": 2
                                                        }
                                                    }
                                                ]
                                            }
                                        }
                                    }
                                },
                                {
                                    "nested": {
                                        "path": "pages",
                                        "query": {
                                            "query_string": {
                                                "query": "search terms",
                                                "fields": [
                                                    "pages.content"
                                                ],
                                                "boost": 1
                                            }
                                        }
                                    }
                                },
                                {
                                    "query_string": {
                                        "query": "search terms",
                                        "fields": [
                                            "filename"
                                        ],
                                        "boost": 4
                                    }
                                }
                            ],
                            "filter": [
                                {
                                    "term": {
                                        "type": {
                                            "value": "document"
                                        }
                                    }
                                }
                            ]
                        }
                    },
                    {
                        "bool": {
                            "minimum_should_match": 1,
                            "should": [
                                {
                                    "query_string": {
                                        "query": "search terms",
                                        "fields": [
                                            "title"
                                        ],
                                        "boost": 5
                                    }
                                },
                                {
                                    "query_string": {
                                        "query": "search terms",
                                        "fields": [
                                            "summary"
                                        ],
                                        "boost": 4
                                    }
                                },
                                {
                                    "query_string": {
                                        "query": "search terms",
                                        "fields": [
                                            "decision_content"
                                        ],
                                        "boost": 3
                                    }
                                }
                            ],
                            "filter": [
                                {
                                    "term": {
                                        "type": {
                                            "value": "dossier"
                                        }
                                    }
                                }
                            ]
                        }
                    }
                ],
                "filter": [
                    {
                        "bool": {
                            "minimum_should_match": 1,
                            "should": [
                                {
                                    "bool": {
                                        "filter": [
                                            {
                                                "term": {
                                                    "type": {
                                                        "value": "document"
                                                    }
                                                }
                                            },
                                            {
                                                "terms": {
                                                    "inquiry_ids": [
                                                        "doc-inq-1"
                                                    ]
                                                }
                                            },
                                            {
                                                "nested": {
                                                    "path": "dossiers",
                                                    "query": {
                                                        "terms": {
                                                            "dossiers.status": [
                                                                "published",
                                                                "preview"
                                                            ]
                                                        }
                                                    }
                                                }
                                            }
                                        ]
                                    }
                                },
                                {
                                    "bool": {
                                        "filter": [
                                            {
                                                "term": {
                                                    "type": {
                                                        "value": "dossier"
                                                    }
                                                }
                                            },
                                            {
                                                "terms": {
                                                    "inquiry_ids": [
                                                        "dos-inq-1",
                                                        "dos-inq-2"
                                                    ]
                                                }
                                            },
                                            {
                                                "terms": {
                                                    "status": [
                                                        "published",
                                                        "preview"
                                                    ]
                                                }
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    }
                ]
            }
        },
        "highlight": {
            "max_analyzed_offset": 1000000,
            "pre_tags": [
                "[[hl_start]]"
            ],
            "post_tags": [
                "[[hl_end]]"
            ],
            "fields": {
                "pages.content": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                },
                "dossiers.title": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                },
                "dossiers.summary": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                },
                "title": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                },
                "summary": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                },
                "decision_content": {
                    "fragment_size": 50,
                    "number_of_fragments": 5,
                    "type": "unified"
                }
            },
            "require_field_match": true,
            "highlight_query": {
                "query_string": {
                    "query": "search terms",
                    "fields": [
                        "title",
                        "summary",
                        "decision_content",
                        "dossiers.summary",
                        "dossiers.title",
                        "pages.content"
                    ]
                }
            }
        },
        "aggs": {
            "subject": {
                "terms": {
                    "field": "subjects",
                    "size": 25,
                    "order": {
                        "_count": "desc"
                    },
                    "min_doc_count": 1
                }
            },
            "source": {
                "terms": {
                    "field": "source_type",
                    "size": 25,
                    "order": {
                        "_count": "desc"
                    },
                    "min_doc_count": 1
                }
            },
            "grounds": {
                "terms": {
                    "field": "grounds",
                    "size": 25,
                    "order": {
                        "_count": "desc"
                    },
                    "min_doc_count": 1
                }
            },
            "judgement": {
                "terms": {
                    "field": "judgement",
                    "size": 25,
                    "order": {
                        "_count": "desc"
                    },
                    "min_doc_count": 1
                }
            },
            "dossiers-department": {
                "nested": {
                    "path": "dossiers"
                },
                "aggs": {
                    "department": {
                        "terms": {
                            "field": "dossiers.departments.name",
                            "size": 25,
                            "order": {
                                "_count": "desc"
                            },
                            "min_doc_count": 1
                        }
                    }
                }
            },
            "dossiers-official": {
                "nested": {
                    "path": "dossiers"
                },
                "aggs": {
                    "official": {
                        "terms": {
                            "field": "dossiers.government_officials.name",
                            "size": 25,
                            "order": {
                                "_count": "desc"
                            },
                            "min_doc_count": 1
                        }
                    }
                }
            },
            "dossiers-period": {
                "nested": {
                    "path": "dossiers"
                },
                "aggs": {
                    "period": {
                        "terms": {
                            "field": "dossiers.date_period",
                            "size": 25,
                            "order": {
                                "_count": "desc"
                            },
                            "min_doc_count": 1
                        }
                    }
                }
            },
            "unique_dossiers": {
                "cardinality": {
                    "field": "dossier_nr"
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr"
                }
            }
        },
        "sort": [
            {
                "decision_date": {
                    "missing": "_last",
                    "order": "asc"
                }
            }
        ]
    },
    "index": "{$this->index}",
    "from": 6,
    "size": 15
}
END,
            json_encode($result->build(), JSON_PRETTY_PRINT)
        );
    }
}
