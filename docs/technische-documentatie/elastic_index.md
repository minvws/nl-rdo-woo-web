# Elasticsearch index specs

<!-- TOC -->
- [Elasticsearch index specs](#elasticsearch-index-specs)
  - [Indices and aliases](#indices-and-aliases)
  - [Mapping versions](#mapping-versions)
  - [Document structure](#document-structure)
    - [Document types](#document-types)
    - [Field groups](#field-groups)
  - [Analysis](#analysis)
  - [Creating and rolling over an index](#creating-and-rolling-over-an-index)
<!-- TOC -->

The mappings live in [config/elastic/](../../config/elastic/) as `mapping-v1.json` … `mapping-vN.json`, with shared
settings in `settings.json`. The newest `mapping-vN.json` is authoritative; this document describes its shape rather than
copying it, so that it does not go stale on the next mapping version.

## Indices and aliases

There is no single hard-coded index name. `Shared\Domain\Search\Index\ElasticConfig` is a service autowired from three
container parameters, so read and write traffic can point at different indices during a rollover:

| Parameter                    | Meaning                             | Value for `minvws` |
|------------------------------|-------------------------------------|--------------------|
| `elasticsearch.index.prefix` | Per-tenant prefix                   | `minvws-`          |
| `elasticsearch.index.read`   | Alias that search queries read from | `minvws-read`      |
| `elasticsearch.index.write`  | Alias that indexing writes to       | `minvws-write`     |

These are defined per tenant in `tenants/<tenant>/config/services.yaml`, and the prefix can be overridden with
`<TENANT>_ELASTICSEARCH_INDEX_PREFIX`. The concrete index behind the aliases is named by whoever created it; the local
setup creates `<tenant>-initial`.

## Mapping versions

Each mapping file declares its own version:

```json
{
    "_meta": { "version": 32 },
    "properties": { }
}
```

`Shared\Domain\Search\Index\Rollover\MappingService` globs `config/elastic/mapping-v*.json` to resolve the latest
version, which is what `latest` means on the command line.

Adding a mapping change means adding a **new** file rather than editing an existing one, so that a running cluster can be
migrated by rolling over from the old version to the new one.

## Document structure

PostgreSQL is the source of truth; Elasticsearch stores a search-oriented projection that can be rebuilt from it. A
document is the unit of indexing, with dossiers nested inside it. This is the inverse of an earlier layout in which
the dossier was the root with documents nested underneath.

```text
<root document>                 a publication, main document, attachment, document or standalone Subject
├── type / toplevel_type / sublevel_type
├── content_for_suggestions, metadata, …
├── dossiers []                 nested: the dossiers this document belongs to
└── pages []                    nested: page number and extracted content
```

Denormalised copies of the parent dossier's fields (`dossier_number`, `title`, `status`, `departments`, `date_from`, …)
also sit at the root, so that search and aggregation do not need the nested documents.

A `Subject` with a landing page is also stored as a root document: its `type` and `toplevel_type` are `subject`, and it
has no `sublevel_type`. This is separate from the existing denormalised `{id, name}` subject reference on publication
documents. The standalone document contains the landing-page title, description, status, organisation, and a flag for
whether its content tree is publicly visible: the landing page and tree must both be published, and the tree must exist.
Subjects with concept landing pages are still indexed; their content-tree visibility flag is false.
Subject indexing logs a warning and skips missing Subjects or Subjects without a landing-page slug; it does not
delete their Elasticsearch documents. Retrieval or indexing exceptions are logged at error level with the Subject UUID
and exception message, consistent with dossier and attachment indexing.
Mapping for `content_tree.title` and `content_tree.body` is reserved for a later
slice; this slice does not populate it. Those fields deliberately do not use `copy_to: content_for_suggestions`, so
hidden content-tree text cannot enter the suggestion dictionary.

### Document types

`Shared\Domain\Search\Index\ElasticDocumentType` enumerates every value `type` can hold, and splits them into:

- **main types** — the ten publication types, for example `dossier` (WooDecision), `covenant`, `annual_report`;
- **sub types** — `document`, `attachment` and the per-type main documents such as `covenant_main_document`.
- **standalone types** — documents such as `subject`, which are neither publications nor their sub-documents.

`toplevel_type` and `sublevel_type` let a query filter on a publication and its sub-documents together.

### Field groups

Broadly, the root properties fall into:

| Group                  | Examples                                                                                                                                                                                                                 |
|------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Identity and type      | `id`, `type`, `toplevel_type`, `sublevel_type`, `document_number`, `publication_context`                                                                                                                                 |
| File metadata          | `file_type`, `file_size`, `mime_type`, `source_type`, `filename`, `document_pages`                                                                                                                                       |
| Document metadata      | `date`, `family_id`, `document_id`, `thread_id`, `judgement`, `grounds`                                                                                                                                                  |
| Dossier (denormalised) | `dossier_number`, `prefixed_dossier_number`, `title`, `summary`, `status`, `document_prefix`, `publication_reason`, `decision`, `decision_date`, `publication_date`, `date_from`, `date_to`, `date_range`, `date_period` |
| Relations              | `departments`, `department_names`, `subject`, `subject_names`, `organisation_id`, `inquiry_ids`, `inquiry_numbers`, `referred_document_numbers`                                                                          |
| Content                | `pages.content`, `content_tree.title`, `content_tree.body`, `content_for_suggestions`                                                                                                                                    |
| Visibility             | `has_visible_content_tree`                                                                                                                                                                                               |

Mapping v32 deliberately retires the legacy `referred_document_nrs` field and retains `referred_document_numbers`.
Unlike the additive mapping change originally specified in #7787, this version also removes that obsolete property.
The current indexing code writes `referred_document_numbers`; older mapping files remain unchanged.

## Analysis

`settings.json` defines two analyzers, both in `config/elastic/settings.json`:

- `dutch` — a custom analyzer with lowercasing, Dutch stop words, keywords, a stemmer and a `stemmer_override` for
  domain-specific terms;
- `autocomplete` — an edge-ngram analyzer used for suggestions.

## Creating and rolling over an index

Index management goes through `Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager`, exposed by the
`woopie:index:*` commands (see [commands.md](commands.md)):

```shell
# Create an index with the newest mapping and point both aliases at it
bin/console --tenant=minvws woopie:index:create minvws-initial latest --read --write

# Create an index with a specific mapping version
bin/console --tenant=minvws woopie:index:create minvws-v32 32

# Move an alias
bin/console --tenant=minvws woopie:index:alias <index> <alias>

bin/console --tenant=minvws woopie:index:delete <index>
```

`create` creates the index, closes it to apply the settings and mapping, then reopens it.

For a rollover with re-indexing, `Shared\Domain\Search\Index\Rollover` holds `RolloverService`, the
`InitiateElasticRolloverCommand` / `SetElasticAliasCommand` messages and a `RolloverCounter` entity that tracks progress
while dossiers are re-ingested and Subjects with landing pages are indexed into the new index. Both jobs stream IDs
from PostgreSQL and enqueue one indexing command per entity. The counter reports standalone Subject counts separately,
and operators can switch the read alias after verifying the re-indexing results.

An isolated Subject write/read/delete check does not prove the complete admin rollover with asynchronous workers.
That requires a separate end-to-end check of the queues, aliases, and rollover progress.

When a new dossier type needs new fields, see the Search support section of [dossier-types.md](dossier-types.md).
