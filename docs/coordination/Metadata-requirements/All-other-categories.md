# Metadata Requirements for all categories (except cat. 14)

Most publication types on the Woo Publication Platform share a similar metadata structure, with a notable exception being [category 14: Woo-verzoeken](category-14-woo-verzoek.md), which is documented separately.

- 1.) Basic metadata
- 2.) Specific publication type metadata
- 3.) Attachment metadata

## 1.) Basic metadata

The basic metadata is the same for all the publications done on the Woo Publication Platform, regardless of the publication type.

| Metadata           | Required | Description                                                                                      | Example                   |
|--------------------|----------|--------------------------------------------------------------------------------------------------|---------------------------|
| title              | yes      | Default filled with the filename of the document being uploaded.                                 | Convenant-SVBCAK-20102022 |
| date_from          | yes      | The start date of the period covered by the publication.                                         | 08-05-2018                |
| date_to            | yes      | The end date of the period covered by the publication.                                           | 08-06-2024                |
| organisation_id    | yes      | The name of the organisation responsible for the publication                                     | PDO                       |
| internal_reference | no       | An identification number to be able to link publications together                                | 32457                     |
| dossier_nr         | yes      | Internal reference number to index and link the publication if needed                            | 3798064-1063791-pdo       |
| document_prefix    | yes      | The document prefix is a code that is unique to the organisation responsible for the publication | A4242Z                    |

## 2.) Publication type specific metadata

The undermentioned metadata is specific for the publication type in question. In reality they will vary slightly across the board.

### 2.1) Covenant metadata

| Metadata            | Required | Description                                                                                                     | Example                                                 |
|---------------------|----------|-----------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| parties             | yes      | The parties between whom the agreement has been made.                                                           | RIVM, CIBG, etc.                                        |
| summary             | yes      | A summary of the content of the covenant. The summary will be displayed on the top of the page of the covenant. | -                                                       |
| previousVersionLink | no       | A link to the previous version of the same covenant.                                                            | -                                                       |
| file_name           | yes      | The name of the file and subsequently the name that is visible on the website.                                  | Convenant-SVBCAK-20102022.pdf                           |
| covenant_reference  | no       | Internal reference to another document that has a reference with this document.                                 | 325365                                                  |
| formal_date         | yes      | The formal date on which the agreement has been made.                                                           | 14-06-2024                                              |
| grounds             | no       | The grounds on which information is redacted in the covenant.                                                   | 5.1.2.b Economische of financiële belangen van de Staat |

### 2.2) Metadata concerning: Annual report, Investigation report, Disposition and complaint judgment

| Metadata             | Required | Description                                                                                                                                               | Example                                                 |
|----------------------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| summary              | yes      | A summary of the contents of the document                                                                                                                 | -                                                       |
| file_name            | yes      | The name of the file and subsequently the name that is visible on the website.                                                                            | -                                                       |
| AttachmentType       | yes      | The type of document that is uploaded. This is used in case of multiple document possibilities in the same category such as annual report and annual plan | Jaarplan                                                |
| *Category*_reference | no       | Internal reference to another document that has a reference with this document.                                                                           | 2353546                                                 |
| formal_date          | yes      | The formal dating used in the document                                                                                                                    | 08-09-2024                                              |
| grounds              | no       | The grounds on which information is redacted in the covenant.                                                                                             | 5.1.2.b Economische of financiële belangen van de Staat |

## 3.) Attachment metadata

Attachments are quite similar across the board for all the publication types. The attachments will be named in combination with the information category. For example DecisionAttachment and AnnualReportAttachment.

| Metadata         | Required | Description                                                                                                  | Example                                         |
|------------------|----------|--------------------------------------------------------------------------------------------------------------|-------------------------------------------------|
| dossier_id       | yes      | A foreign key that relates to other tables                                                                   | 43452431                                        |
| formal_date      | yes      | The formal date that is mentioned in the document                                                            | 11-03-2021                                      |
| type             |          | Enum value that tells what type of document the attachment is.                                               | c_6d494ab6                                      |
| created_at       | yes      | Timestamp of the time the decision document is uploaded.                                                     | '2023-05-07-14:30:00'                           |
| updated_at       | yes      | Timestamp of the time the decision document has been changed.                                                | '2023-07-07-14:30:00'                           |
| file_mimetype    | yes      | A label used to identify the type of data contained in a file.                                               | document/.pdf                                   |
| file_path        | yes      | The location of the file                                                                                     | C:\Users\Username\Documents\Project\Report.docx |
| file_size        | yes      | The size of the file                                                                                         | 234MB                                           |
| file_type        | yes      | The internal type used, a simplification of the file_mimetype                                                | .pdf                                            |
| file_name        | yes      | The original upload name. If the file is uploaded with "woo-decision01" as its name, it will take that name. | projectxyz                                      |
| file_uploaded    | yes      | A boolean variable for if a file is uploaded or not.                                                         | true/false                                      |
| file_source_type | yes      | The original source of the document before it was made into a .pdf                                           | whatsapp, spreadsheet, word document en email.  |
