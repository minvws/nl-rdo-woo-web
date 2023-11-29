# Elasticsearch index specs

For the actual mapping, see the `config/elastic/mapping.json`. Additional settings are found in `config/elastic/settings.json`.

Note that we currently use a single index as defined in `\App\ElasticConfig::INDEX`.

```json
{
    "properties": {
        "dossier_nr": {                     // Unique dossier number. This is also the ID of the document.
            "type": "keyword"
        },
        "title": {                          // Dossier title
            "type": "text",
            "analyzer": "dutch",
            "fields": {
                "suggest": {
                    "type": "completion",
                    "analyzer": "dutch"
                }
            }
        },
        "status": {                         // Status of the dossier
            "type": "keyword"
        },
        "summary": {                        // Summary of the dossier
            "type": "text",
            "analyzer": "dutch",
            "fields": {
                "suggest": {
                    "type": "completion",
                    "analyzer": "dutch"
                }
            }
        },
        "document_prefix": {                // Prefix of documents found in this dossier
            "type": "keyword"
        },
        "content": {                        // Extracted content
            "type": "text",
            "analyzer": "dutch",
            "fields": {
                "suggest": {
                    "type": "completion",
                    "analyzer": "dutch"
                }
            }
        },
        "documents": {                      // Documents are nested object within a dossier
            "type": "nested",
            "properties": {
                "document_nr": {            // Document number (unique within dossier, and prefixed with document_prefix)
                    "type": "keyword"
                },
                "dossier_nr": {             // Dossier number within this document
                    "type": "keyword"
                },
                "mimetype": {               // Mimetype of the document
                    "type": "keyword"
                },
                "filesize": {               // Size in bytes of the document
                    "type": "integer"
                },
                "document.pages": {         // Number of pages within the document (if a pageable document)
                    "type": "integer"
                },
                "audio.duration": {         // Duration in seconds of audio/video
                    "type": "integer"
                },
                "source_type": {            // Type of document (document, audio, video, spreadsheet, email etc)
                    "type": "keyword"
                },
                "class": {                  // Document class (inventory, document or decision)
                    "type": "keyword"
                },
                "file_type": {              // Type of the locally ingested file (PDF, mostly)
                    "type": "keyword"
                },  
                "date": {                   // Document date. Not the same as the creation date.
                    "type": "date"
                },
                "filename": {               // Filename of the document to the outside world
                    "type": "keyword"
                },
                "family_id": {              // Family id of the document when grouped together with others.
                    "type": "keyword"
                },
                "document_id": {            // Internal document ID.
                    "type": "keyword"
                },
                "thread_id": {              // If threaded like emails, this is the thread id.
                    "type": "keyword"
                },
                "judgement": {              // Publication decision of the document (published, not published, partially published)
                    "type": "keyword"
                },
                "grounds": {                // Reasons for the publication decision (privacy, security, etc.)
                    "type": "keyword"
                },
                "subjects": {               // Labels concerning this document
                    "type": "keyword"
                },
                "period": {                 // Date period this document reflects
                    "type": "keyword"
                },
                "content": {                // Extracted content of the document
                    "type": "text",
                    "analyzer": "dutch",
                    "fields": {
                        "suggest": {
                            "type": "completion",
                            "analyzer": "dutch"
                        }
                    }
                },
                "pages": {                          // Pages is a nested object of all pages within the document
                    "type": "nested",
                    "properties": {
                        "page_nr": {                // Page number (starts at 1)
                            "type": "integer"
                        },
                        "document_nr": {            // Document number for this page
                            "type": "keyword"
                        },
                        "dossier_nr": {             // Dossier number for this page
                            "type": "keyword"
                        },
                        "content": {                // Actual extracted content of the page
                            "type": "text",
                            "analyzer": "dutch",
                            "fields": {
                                "suggest": {
                                    "type": "completion",
                                    "analyzer": "dutch"
                                }
                            },
                            "copy_to": {                // Copy the content to the document content fields
                                "documents.content"
                            }
                        }
                    }
                }
            }
        }
    }
}
```
