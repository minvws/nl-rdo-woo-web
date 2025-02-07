# Woo Platform

<!-- TOC -->
- [Woo Platform](#woo-platform)
  - [Creating dossiers](#creating-dossiers)
    - [Create dummy dossiers and documents](#create-dummy-dossiers-and-documents)
    - [Create and upload real data](#create-and-upload-real-data)
<!-- TOC -->

You must create a dossier and add documents in order to use the site.

## Creating dossiers

### Create dummy dossiers and documents

You can either create a real dossier and upload real documents, or if you do not need to use the
actual PDF file, you can generate dossiers and documents on the fly. For this, use the following
command:

```shell
    docker-compose exec app bin/console woopie:generate:documents
```

Press ctrl-C when you feel you have enough documents.

### Create and upload real data

You can create a dossier after logging in at `localhost:8000/balie/dossiers`. Fill in the dossier
you want, and add a XLS file as inventory file.

After this, you can upload the PDF files to the dossier. You can do this by clicking on the dossier
'documents' button, and drop the ZIP file in the dropzone.

Once completed, you must change the status of the dossier to 'Published'. This will take a few
attempts as you must change to 'completed' => 'partial' => 'published'.

After this, you can press the "ingest" button, so the documents are ingested into elasticsearch.

After this point, you can search on the main page.
