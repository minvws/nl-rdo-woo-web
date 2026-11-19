# Demo data

Fills a local development environment with a large, realistic data set: dossiers of every type and status, documents with all judgements, attachments, production reports, inquiries and generated PDF files.

## Load

```shell
task app:loaddemodata                    # 95 dossiers, about 570 documents
task app:loaddemodata DOSSIERS=2000      # any number from 0 to 100000
task app:loaddemodata TENANT=minfin
```

The tenant defaults to `minvws`. The base fixtures (`task console:loadfixtures`) must have been loaded first: the demo data uses the existing organisation, departments and subjects.

The task can be run as often as you like. It first asks for confirmation (use `task -y` to skip) and then:

1. stops the workers of the tenant, so they do not write to the search index while it is replaced;
2. clears **all** dossiers, documents and inquiries of the tenant (not only demo data), the search index, the queues and the file storage with `cleansheet`. Users and subjects are kept;
3. loads the demo data and starts the workers again.

Loading the story directly (`foundry:load-fixtures demo`) does not clear anything. It stops with a message when demo data is already present.

### Number of dossiers

`DOSSIERS` sets the number of dossiers.
The default of 95 contains every combination of dossier type and status twice, and one dossier with 150 documents.
With fewer dossiers you get the first part of that set, with more the combinations are repeated.
A Woo decision has 4 to 20 documents, so the number of documents, files, attachments and inquiries grows with it.

As a rule of thumb, loading takes about 2 minutes per 1000 dossiers (`DOSSIERS=0` only clears), and the workers need much longer afterwards to generate the thumbnails and index the pages, see below.

## What you get

- **Woo decisions**: every decision type in the statuses concept, scheduled, preview and published.
  They have documents (public, partially public, already public, not public), families and threads,
  withdrawn and suspended documents, documents without a file and a production report where required.
  One dossier has 150 documents.
- **Other dossier types**: all nine types in the statuses concept, scheduled and published, with a main document and attachments.
- **Inquiries** linked to dossiers and documents, with an inventory file.
- **Files**: every file is a unique, generated PDF of 1 to 15 pages (a few long documents of 25 to 60 pages), stored in the file storage. Production reports and inquiry inventories are spreadsheets.

All dossiers that are not in concept are complete (`completed` flag set by the application itself). If that is not the case for some dossier, the load lists them at the end.

## After loading

The workers need to process the files before everything is searchable and has thumbnails:

- Text extraction and indexing run through the `ingestor` queue. The progress is visible on the statistics page in the admin, or with `task console:cmd:messenger:stats`.
- `task console:cmd:woopie:check:storage` lists missing files and thumbnails. When the queue is empty, it should report no missing files.

Thumbnail generation runs OCR on every page and is CPU bound. To speed it up, run more workers and limit Tesseract to one thread per process, in your local `compose.override.yml`:

```yaml
services:
  worker_minvws:
    environment:
      OMP_THREAD_LIMIT: "1"
```

```shell
task dc SUB_CMD="up -d --scale worker_minvws=6 worker_minvws"
```

## Login

Create a super admin (the command prints a temporary password and the 2FA secret):

```shell
task console:user:create TENANT=minvws -- "you@example.org" "Your Name" --super-admin
```

## Changing the data

The data is defined in `tests/Story/DemoDataStory.php` (what is created) and `tests/Story/Demo/` (the PDF and spreadsheet generation). It uses the Foundry factories in `tests/Factory`.
