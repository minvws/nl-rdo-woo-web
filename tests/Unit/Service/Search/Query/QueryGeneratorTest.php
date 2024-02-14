<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query;

use App\ElasticConfig;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\AggregationGenerator;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\FacetListFactory;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class QueryGeneratorTest extends MockeryTestCase
{
    private string $index = ElasticConfig::READ_INDEX;
    private QueryGenerator $queryGenerator;
    private FacetInputFactory $facetInputFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facetInputFactory = new FacetInputFactory();
        $contentAccessConditions = new ContentAccessConditions();
        $facetConditions = new FacetConditions();
        $searchTermConditions = new SearchTermConditions();
        $facetListFactory = new FacetListFactory();

        $this->queryGenerator = new QueryGenerator(
            new AggregationGenerator(
                $contentAccessConditions,
                $facetConditions,
                $searchTermConditions
            ),
            $contentAccessConditions,
            $facetConditions,
            $searchTermConditions,
            $facetListFactory,
        );
    }

    public function testCreateQueryWithMinimalConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new Config(
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
            )
        );

        $this->assertJsonStringEqualsJsonString(
            <<<END
{
    "body": {
        "docvalue_fields": [
            "type",
            "document_nr",
            "document_prefix",
            "dossier_nr"
        ],
        "_source": false,
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
                    "field": "dossier_nr",
                    "precision_threshold": 40000
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr",
                    "precision_threshold": 40000
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
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
                searchType: Config::TYPE_DOSSIER,
            )
        );

        $this->assertJsonStringEqualsJsonString(
            <<<END
{
    "body": {
        "docvalue_fields": [
            "type",
            "document_nr",
            "document_prefix",
            "dossier_nr"
        ],
        "_source": false,
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
                    "field": "dossier_nr",
                    "precision_threshold": 40000
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr",
                    "precision_threshold": 40000
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
                facetInputs: $this->facetInputFactory->create(),
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
        "docvalue_fields": [
            "type",
            "document_nr",
            "document_prefix",
            "dossier_nr"
        ],
        "_source": false,
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
                                                        "simple_query_string": {
                                                            "query": "search terms",
                                                            "default_operator": "and",
                                                            "fields": [
                                                                "dossiers.title"
                                                            ],
                                                            "boost": 3
                                                        }
                                                    },
                                                    {
                                                        "simple_query_string": {
                                                            "query": "search terms",
                                                            "default_operator": "and",
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
                                            "simple_query_string": {
                                                "query": "search terms",
                                                "default_operator": "and",
                                                "fields": [
                                                    "pages.content"
                                                ],
                                                "boost": 1
                                            }
                                        }
                                    }
                                },
                                {
                                    "simple_query_string": {
                                        "query": "search terms",
                                        "default_operator": "and",
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
                                    "simple_query_string": {
                                        "query": "search terms",
                                        "default_operator": "and",
                                        "fields": [
                                            "title"
                                        ],
                                        "boost": 5
                                    }
                                },
                                {
                                    "simple_query_string": {
                                        "query": "search terms",
                                        "default_operator": "and",
                                        "fields": [
                                            "summary"
                                        ],
                                        "boost": 4
                                    }
                                },
                                {
                                    "simple_query_string": {
                                        "query": "search terms",
                                        "default_operator": "and",
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
                "simple_query_string": {
                    "query": "search terms",
                    "default_operator": "and",
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
                        "_key": "asc"
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
                    "field": "dossier_nr",
                    "precision_threshold": 40000
                }
            },
            "unique_documents": {
                "cardinality": {
                    "field": "document_nr",
                    "precision_threshold": 40000
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
