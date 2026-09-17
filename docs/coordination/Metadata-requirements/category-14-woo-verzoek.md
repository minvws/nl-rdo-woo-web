# Category 14: Woo-verzoek metadata

The metadata requirements for category 14 consist of 3 separate parts. The first there is the *basic metadata* such as subject,
period and decision number. The second part is the *decision metadata* concerning the decision that has been made on the subject.
The third part consists of the production report which includes all the metadata for all the separate documents that are included
in the publication.

- 1.) Basic metadata
- 2.) Woo Decision metadata
- 3.) Production report metadata

## 1. Basic metadata

The basic metadata is the same for all the publications done on the open.minvws.nl platform.

| Dutch terminology               | Metadata        | Required | Description                                                                                              | Example                  |
|---------------------------------|-----------------|----------|----------------------------------------------------------------------------------------------------------|--------------------------|
| onderwerp van het besluit       | title           | Yes      | Default filled with the filename of the document being uploaded.                                         | Woo verzoek abcd167      |
| beginmaand                      | date_from       | no       | TThe start date of the period concerning this decision                                                   | '2024-05-08'             |
| eindmaand                       | date_to         | no       | The end date of the period concerning this decision                                                      | 2024-05-08'              |
| verantwoordelijk bestuursorgaan | organisation_id | yes      | The name of the organisation that is responsible for the publication                                     | PDO                      |
| onderwerp                       | default_subject | no       | A list of default subjects concerning decisions.                                                         | Vacciniaties & Medicatie |
| dossiernummer                   | dossier_nr      | yes      | Internal reference number to index and link the publication if needed                                    | 3798064-1063791-pdo      |
| prefix                          | document_prefix | yes      | The document prefix is a code that is unique to the organisation that is responsible for the publication | A4242Z                   |

## 2. Woo Decision metadata

The decision document is a legal document in which the legal reason or grounds for refusal for publishing the documents are explained.

| Dutch terminology | Metadata    | Required | Description                                                                                                                                                        | Example               |
|-------------------|-------------|----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------|
| besluit           | decision    | yes      | The outcome of a decision can have 5 possible outcomes. *Gedeeltelijk openbaar*, *Reeds openbaar*, *Openbaarmaking*, *Geen openbaarmaking* en *Niets aangetroffen* | Gedeeltelijk openbaar |
| samenvatting      | summary     | yes      | A summary of the decision that is displayed on the top of the page where the decision is published                                                                 | -                     |
| formele datum     | formal_date | yes      | The date on which the decision has been made                                                                                                                       | 11-03-2021            |

### Woo decision document metadata

| Metadata         | Required | Description                                                                                                  | Example                                         |
|------------------|----------|--------------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| dossier_id       | yes      | A foreign key that relates to other tables                                                                   | 43452431                                        |
| created_at       | yes      | Timestamp of the time the decision document is uploaded.                                                     | '2023-05-07-14:30:00'                           |
| updated_at       | yes      | Timestamp of the time the decision document has been changed.                                                | '2023-07-07-14:30:00'                           |
| file_mimetype    | yes      | A label used to identify the type of data contained in a file.                                               | document/.pdf                                   |
| file_path        | yes      | The location of the file                                                                                     | C:\Users\Username\Documents\Project\Report.docx |
| file_size        | yes      | The size of the file                                                                                         | 234MB                                           |
| file_type        | yes      | The internal type used, a simplification of the file_mimetype                                                | .pdf                                            |
| file_source_type | yes      | The original source of the document before it was made into a .pdf                                           | whatsapp, spreadshee, word document en email.   |
| file_name        | yes      | The original upload name. If the file is uploaded with "woo-decision01" as its name, it will take that name. | projectxyz                                      |
| file_uploaded    | yes      | A boolean variable for if a file is uploaded or not.                                                         | true/false                                      |

### Woo decision attachment metadata (optional)

| Metadata         | Required | Description                                                                                                  | Example                                         |
|------------------|----------|--------------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| dossier_id       | yes      | A foreign key that relates to other tables                                                                   | 43452431                                        |
| formal_date      | yes      | The formal date that is mentioned in the document                                                            | 11-03-2021                                      |
| type             |          | enum value that tells what type of document the attachment is.                                               | c_6d494ab6                                      |
| created_at       | yes      | Timestamp of the time the decision document is uploaded.                                                     | '2023-05-07-14:30:00'                           |
| updated_at       | yes      | Timestamp of the time the decision document has been changed.                                                | '2023-07-07-14:30:00'                           |
| file_mimetype    | yes      | A label used to identify the type of data contained in a file.                                               | document/.pdf                                   |
| file_path        | yes      | The location of the file                                                                                     | C:\Users\Username\Documents\Project\Report.docx |
| file_size        | yes      | The size of the file                                                                                         | 234MB                                           |
| file_type        | yes      | The internal type used, a simplification of the file_mimetype                                                | .pdf                                            |
| file_name        | yes      | The original upload name. If the file is uploaded with "woo-decision01" as its name, it will take that name. | projectxyz                                      |
| file_uploaded    | yes      | A boolean variable for if a file is uploaded or not.                                                         | true/false                                      |
| file_source_type | yes      | The original source of the document before it was made into a .pdf                                           | whatsapp, spreadsheet, word document en email.  |

*All the file_ datafields are merged in one embedded data type.

## 3. Production report metadata

The production report is an Excel sheet that is an export of the redaction software that contains all the metadata of the documents.
The metadata are then linked to the actual documents on the website, which makes the documents searchable.

| Dutch terminology                 | Metadata      | Required | Description                                                                                                                                                                                                                 | Example                                                                                                      |
|-----------------------------------|---------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| documentID                        | document_id   | yes      | A number that identifies the specific file in this matter.                                                                                                                                                                  | 1106947                                                                                                      |
| matterID                          | matter        | yes      | A number that is assigned to the group of documents concerning the woo-decision. If empty the inquiry_id can be used.                                                                                                       | 17                                                                                                           |
| familyID                          | family_id     | nee      | Document ID of the email to which this document belongs. The respective document may also appear in the decision, but this is not mandatory.                                                                                | 982509                                                                                                       |
| email Thread ID                   | thread_id     | no       | number for a specific email string to link emails that are connect                                                                                                                                                          | 982509                                                                                                       |
| documentnaam                      | file_name     | yes      | file name of the document.                                                                                                                                                                                                  | Technische briefing 25 maart 2020.docx                                                                       |
| file type                         | file_type     | no       | A classification what kind of file type the document is.                                                                                                                                                                    | Email, PDF, Word, Chatberichten                                                                              |
| datum                             | document_date | yes      | The date of document creation.                                                                                                                                                                                              | 12/7/2020 9:51 AM UTC                                                                                        |
| beoordeling                       | judgement     | yes      | The judgement if the information that is contained in the file will be made public or not. 5 possible judgments: *Gedeeltelijk openbaar*, *Reeds openbaar*, *Openbaarmaking*, *Geen openbaarmaking* en *Niets aangetroffen* | Deels Openbaar                                                                                               |
| weigeringsgrond/beoordelingsgrond | grounds       | yes      | The grounds on which information is not made public based on the judgement that is made.                                                                                                                                    | 5.1.1c; 5.1.2e; 5.1.5                                                                                        |
| opgeschort                        | suspended     | no       | If a document is suspended                                                                                                                                                                                                  | true/false                                                                                                   |
| toelichting                       | remark        | no       | Remark or addition to the contents of the document                                                                                                                                                                          | Notitie, Q&A, brief                                                                                          |
| publieke Link                     | links         | no       | public link to the location if information is already public                                                                                                                                                                | [rivm.nl example](https://www.rivm.nl/documenten/deelname-covid-19-vaccinatie-in-nederland-23-februari-2021) |
| gerelateerdID                     | related_id    | no       | combination of matter and document_id of a document that is linked                                                                                                                                                          | 17-983015                                                                                                    |
| zaaknummer                        | inquiry_id    | no       | Number that identifies the inquiry                                                                                                                                                                                          | 2021.018.019.021b                                                                                            |
