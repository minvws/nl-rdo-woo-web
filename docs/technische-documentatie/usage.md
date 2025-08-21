# Woo Platform

<!-- TOC -->
- [Woo Platform](#woo-platform)
  - [Creating dossiers](#creating-dossiers)
<!-- TOC -->

You must create a dossier and add documents in order to use the site.

## Creating dossiers

You can create a dossier after logging in at `localhost:8000/balie/dossiers`. Fill in the dossier
you want, and add a XLS file as inventory file.

After this, you can upload the PDF files to the dossier. You can do this by clicking on the dossier
'documents' button, and drop the ZIP file in the dropzone.

Once completed, you must change the status of the dossier to 'Published'. This will take a few
attempts as you must change to 'completed' => 'partial' => 'published'.

After this, you can press the "ingest" button, so the documents are ingested into elasticsearch.

After this point, you can search on the main page.
