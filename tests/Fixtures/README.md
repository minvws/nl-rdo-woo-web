# Fixtures

Fixtures are used for loading data into the system to test specific scenarios.

## JSON definition

A fixture is defined using a JSON file, see one of the existing JSON files in this folder as an example.
The current naming convention is to use a number prefix in the filenames like `000-email.json` though this is not enforced. Using another naming convention is possible.

The JSON file contains the following properties:

- `name`: The fixture name / identifier. Mainly for keeping fixtures organized and feedback from the `woopie:load:fixture` command, not actually imported into the system.
- `description`: Description / notes for the fixture, not imported into the system. Optional.
- `dossiers`: A set of dossiers in the fixture, should be at least one but can also be multiple.

Each entry within `dossiers` has the following properties:

| Property           | Description                                                                          | Mandatory | Type / format              |
|--------------------|--------------------------------------------------------------------------------------|-----------|----------------------------|
| id                 | Used by the system as a identifier.                                                  | Yes       | string                     |
| document_prefix    | Should be unique to prevent docNr conflicts for documents across multiple dossiers.  | Yes       | string                     |
| title              | The title of the dossier                                                             | Yes       | string                     |
| summary            | The summary of the dossier                                                           | Yes       | string                     |
| department         | Use the `name` of one of the existing values in the admin (Balie).                   | Yes       | string or array of strings |
| period_from        | The start date of the dossier                                                        | Yes       | string YYYY-MM-DD          |
| period_to          | The end date of the dossier                                                          | Yes       | string YYYY-MM-DD          |
| decision           | One of `already_public`, `public`, `partial_public`, `not_public` or `nothing_found` | Yes       | string                     |
| publication_reason | The publication reason of the dossier: `wob_request`, `woo_request` or `woo_active`  | Yes       | string                     |
| inventory_path     | Relative path to the inventory file (XLSX). See detailed explanation below.          | Yes       | string                     |
| document_path      | Relative path to the document package (ZIP). See detailed explanation below.         | No        | string                     |
| status             | Status of the dossier. One of: `concept`, `completed`, `preview` or `published`      | Yes       | string                     |
| fake_documents     | See detailed explanation below.                                                      | No        | array of objects           |

**Important note:**  
Fixtures currently don't support dossier updates! So loading the same dossier fixture twice without resetting the data will result in two similar dossiers existing within the system.
This is something you probably want to prevent during testing.
Loading multiple different fixtures is fine, but if you want to reload/refresh the fixture you should first reset the system to a clean sheet.
See the instructions on that down below.

### Inventory file

This file is mandatory for each JSON definition.

You can use an inventory file from a test package or one of the existing ones in this folder as a starting point.
The Excel file has the following columns:

| Column            | Description                                                                                      | Mandatory column | Mandatory value | Type   |
|-------------------|--------------------------------------------------------------------------------------------------|------------------|-----------------|--------|
| family            | Used for 'Related documents' functionality, optionally use the `ID` value here.                  | Yes              | Yes             | Int    |
| ID                | If empty row is skipped.                                                                         | Yes              | No              | Int    |
| Thread ID         | Used for relating multiple emails to one thread.                                                 | Yes              | No              | Int    |
| Document          | If missing `<dossierDocumentPrefix>-<zaakNr>.pdf` is assumed                                     | Yes              | No              | String |
| File Type         | If not one of the values of `$types` in `SourceType.php` will use `unknown`                      | Yes              | No              | String |
| Datum             | Must be a valid YYYY-MM-DD format                                                                | Yes              | Yes             | String |
| Beoordeling       | Multiple values possible by using `;` as a separator in the string.                              | Yes              | No              | String |
| Beoordelingsgrond | Multiple values possible by using `;` as a separator in the string.                              | Yes              | No              | String |
| Onderwerp         | Multiple values possible by using `;` as a separator in the string.                              | Yes              | No              | String |
| Periode           | For example the string: `December 2012 - september 2020`                                         | Yes              | No              | String |
| ZaakNr            | Links documents to cases (inquiries). Multival with `;`. If casenr not known it will be created. | No               | No              | String |
| Opgeschort        | Values `true`, `ja`, `yes`, `1`, `y` and `j` are accepted as `true`, other values as `false`     | No               | No              | String |

An inventory file may contain one or more rows. Even 0 rows (just the header) is possible, but not very useful...

### Document package

This file is not mandatory, if it doesn't exist document processing will be skipped but the dossier will still be created.

A document package is basically just a ZIP file containing one or more document files.
The system will try to process all files in the zipfile with the `.pdf` extension.

The filenames should use the pattern `<id>.pdf` where `id` should match with an ID from an inventory file.

### Fake documents

The `fake_documents` property is a special feature that allows fixtures to insert documents easily without having to add them to the inventory and without providing actual documents.

*Important note: this bypasses several parts of the system, for instance OCR. This might be ok for some testcases though.*

The property should be a JSON array with objects. An example omitting most other dossier properties to keep it short:

```json
{
  "name": "...",
  "dossiers": [
    {
      "id": "TST-123",
      "fake_documents": [
        {
          "document_id": 456,
          "pages": [
            "This is the fake content for the first page",
            "And this is fake content for the second page"
          ]
        },
        {
          "document_id": 789,
          "pages": [
            "More fake content..."
          ]
        }
      ]
    }
  ]
}
```

All properties are optional, when not provided a default value will be generated.

| Column        | Description                                               | Type             | Default value                |
|---------------|-----------------------------------------------------------|------------------|------------------------------|
| document_id   | Relates to `ID` in the inventory                          | Int              | random int                   |
| document_nr   |                                                           | String           | `PREF-<document id>`         |
| created_at    | Format `2023-07-20 15:14:13`                              | String           | `now`                        |  
| updated_at    | Format `2023-07-20 15:14:13`                              | String           | `now`                        |  
| document_date | Format `2023-07-20 15:14:13`                              | String           | `now`                        |
| pages         | Array of strings, each entry is the content for one page. | Array            | No                           |
| source_type   |                                                           | String           | `pdf`                        |
| duration      |                                                           | Int              | 0                            |
| family_id     | Similar to `family` in inventory                          | String           | `<document id>`              |
| thread_id     | Similar to `thread Id` in inventory                       | Int              | 0                            |
| summary       |                                                           | String           | empty string                 |
| uploaded      |                                                           | Bool             | `true`                       |
| filename      |                                                           | String           | `document-<document id>.pdf` |
| file_type     |                                                           | String           | `application/pdf`            |
| mime_type     |                                                           | String           | `pdf`                        |
| subjects      | Similar to `onderwerp` in inventory                       | Array of strings | empty array                  |
| suspended     | Similar to `opgeschort` in inventory                      | Bool             | `false`                      |
| withdrawn     | Marks a document as `withdrawn` similar to admin action.  | Bool             | `false`                      |

## Commands

### Loading fixtures into the system

```shell
    php bin/console woopie:load:fixture <path/to/fixture-file.json>
```

Or when using docker:

```shell
    docker-compose exec app bin/console woopie:load:fixture tests/Fixtures/000-email.json
```

Ensure queued processing is executing by running:

```shell
    docker-compose exec app make consume
```

### Reset the system to a clean sheet

This removes all dossiers, documents and inquiries from the database and elasticsearch. Also clears any worker messages still in the queue.
Any other data, like logins or departments will not be affected.

This is very important, as the system currently does not allow duplicate documents in multiple dossiers, which is exactly what will happen if you load the same fixture twice.

```shell
    docker-compose exec app bin/console woopie:dev:clean-sheet
```

It will clear dossiers, documents and inquiries from the database.
The ElasticSearch index and all message queues will also be emptied.

Optionally you can remove all users by including the `-u` command flag.
Optionally you can remove all prefixes by including the `-p` command flag.

By default the command will ask for confirmation. For automation you can add a `--force` flag, this removes the confirmation.
