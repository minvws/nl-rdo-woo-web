# Woo Publication Platform

<!-- TOC -->
- [Woo Publication Platform](#woo-publication-platform)
  - [Getting data into a local instance](#getting-data-into-a-local-instance)
  - [Creating a publication by hand](#creating-a-publication-by-hand)
    - [Wizard steps](#wizard-steps)
    - [A WooDecision specifically](#a-woodecision-specifically)
  - [Publication statuses](#publication-statuses)
  - [Ingest](#ingest)
<!-- TOC -->

This is the developer-facing note on how to get data into a local instance. For the functional walkthrough aimed at
end users, see the user manual in [docs/gebruikershandleiding/](../gebruikershandleiding/), which is published at
<https://open.minvws.nl/documentatie>.

## Getting data into a local instance

The fast path is fixtures, not the UI. `task setup` already loads the example fixtures for every local tenant, and you
can reload them at any time:

```shell
# Example fixtures
task console:loadfixtures TENANT=minvws

# The dataset the E2E tests use
task rf:fixtures:load:e2e

# Start over: clears dossiers, inquiries, the ES index and the MinIO buckets
task cleansheet
```

## Creating a publication by hand

Log in to the admin (balie) — <http://admin-minvws.local/balie/login> with the default docker setup, see
[development_install.md](development_install.md#step-6-browse-to-the-site) — and go to the dossiers overview. Creating a
publication means picking a **publication type** first — there are ten, from WooDecision to DraftDecision, and the type
decides which steps follow. See [dossier-types.md](dossier-types.md) for the full list.

### Wizard steps

Each type defines its own steps in its `DossierTypeConfigInterface` implementation, drawn from `StepName`:

| Step          | Purpose                                                      |
|---------------|--------------------------------------------------------------|
| `details`     | Title, dossier number, period, departments, subject          |
| `decision`    | The decision itself. WooDecision only                        |
| `documents`   | The documents belonging to the publication. WooDecision only |
| `content`     | The main document and any attachments                        |
| `publication` | Review and publish, or schedule for a future date            |

An AnnualReport, for example, has `details`, `content` and `publication`. A WooDecision has `details`, `decision`,
`documents` and `publication`.

Each step has a *concept* and an *edit* mode: concept while the publication is still being drafted, edit once it has been
published.

### A WooDecision specifically

1. **Details** — the usual metadata.
2. **Decision** — the decision and its date.
3. **Documents** — upload the **production report** ("productierapport"), the spreadsheet listing the documents and their
   metadata, then upload the document files themselves. The production report is processed asynchronously by a worker, so
   the step reports a status while it runs. The downloadable document list ("inventarislijst") that the public site
   offers is generated from this, and is a different thing — see [terminology.md](terminology.md).
4. **Publication** — publish, or schedule.

## Publication statuses

`DossierStatus` has six values. There is no `completed → partial → published` sequence.

| Status      | Meaning                                                                     |
|-------------|-----------------------------------------------------------------------------|
| `new`       | New, and might not even be persisted yet                                    |
| `concept`   | Might not be complete, and has no publication scheduled yet                 |
| `scheduled` | No longer a concept; preview and/or publication is planned at a future date |
| `preview`   | In preview mode, not yet available for anybody                              |
| `published` | Published and available for anybody                                         |
| `deleted`   | Deleted and no longer available                                             |

`DossierStatus::publiclyAvailableCases()` returns `preview` and `published`, and `ApplicationId::PUBLIC` uses that to
decide what the public site may load. The admin can additionally reach `concept` and `scheduled`.

Transitions are governed by a Symfony workflow per publication type, so the admin only offers the ones the workflow
allows.

## Ingest

There is no "ingest" button. Publishing dispatches the work: the publication is indexed into Elasticsearch, content is
extracted, thumbnails are generated. Once that completes the publication is searchable on the public site.

Re-ingesting is a command, useful when you have changed a mapping or want to rebuild derived data:

```shell
bin/console --tenant=minvws woopie:ingest:dossier <prefix> <dossierNumber>
```

The workers must be running for anything to happen; they are started as part of `task up`. See
[commands.md](commands.md) for the other maintenance commands and [technical.md](technical.md#ingest) for what ingest
does.
