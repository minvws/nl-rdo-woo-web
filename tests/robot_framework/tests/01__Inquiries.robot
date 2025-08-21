*** Settings ***
Documentation       Tests that focus on using the inquiry system
...                 This is named 01 because we want to run this first, when the queue is empty, because the first test waits for the queue to be empty.
Resource            ../resources/Inquiry.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  inquiries


*** Test Cases ***
Preview Inquiry Access
  [Documentation]  Verify preview access to a dossier using inquiry page
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport4.xlsx
  ...  documents=files/inquiries/documenten4.zip
  ...  number_of_documents=3
  ...  publication_status=Gepland
  Wait For Queue To Empty
  # Verify the document can be found through the inquiry
  Click Inquiries
  Open Inquiry  2021-01
  Click Search Through Documents In Inquiry
  Verify Search Result Count  3 resultaten in 1 publicatie
  Initiate Search  huppeldepup.docx
  Verify Search Result Count  1 resultaat in 1 publicatie
  Click  "huppeldepup.docx"
  ${document_url} =  Get URL
  # Verify the document can't be found normally
  New Context  locale=nl-NL
  New Page
  Go To Public
  Search On Public For  huppeldepup.docx  0 resultaten
  # Verify the document page can't be accessed normally
  Go To  ${document_url}
  Verify Symfony Error  not found

Inquiry With Multiple Dossiers
  [Documentation]  Create an inquiry with multiple dossiers
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport7a.xlsx
  ...  documents=files/inquiries/documenten7a.zip
  ...  number_of_documents=9
  ...  publication_status=Concept
  VAR  ${concept_dossier} =  ${DOSSIER_REFERENCE}
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  VAR  @{concept_doc_ids} =  3601  3602
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport7b.xlsx
  ...  documents=files/inquiries/documenten7b.zip
  ...  number_of_documents=3
  Append To List  ${dossier_ids}  ${DOSSIER_REFERENCE}
  Verify That Inventory Doesn't Contain Concept Docs  ${concept_doc_ids}
  Publish The Concept Dossier  ${concept_dossier}
  Click Inquiries
  Open Inquiry  2019-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=3  nr_of_partially_published_docs=4  nr_of_unpublished_docs=1
  VAR  @{document_ids} =  3601  3602  3603  3604  3609  3611  3613
  Verify Inquiry Documents  ${document_ids}

Verify PDF Preview Thumbnail
  [Documentation]  Depends on the previous test, since it needs a fully ingested dossier.  TODO: This should actually move to WooDecision
  Click Inquiries
  Open Inquiry  2021-01
  Click First Document In Inquiry
  Verify Document Preview Thumbnails
  ${url} =  Get Attribute  (//ol[@data-e2e-name="preview-list"]/li)[1]//a  href
  Generic Download URL  ${url}
  Generic Download Click  //a[@data-e2e-name="download-file-link"]

Verify Download Of Full Inquiry
  [Documentation]  Create a test dossier for an inquiry and then donwload the full inquiry
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport8.xlsx
  ...  documents=files/inquiries/documenten8.zip
  ...  number_of_documents=5
  ...  publication_status=Gepland
  Click Inquiries
  Open Inquiry  8000-01
  Download Inquiry Archive

Link Inquiries Using Production Report
  [Documentation]  Create a WooDecision that is part of multiple inquiries using Production Report upload
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport1.xlsx
  ...  documents=files/inquiries/documenten1.zip
  ...  number_of_documents=9
  Click Inquiries
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2024-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=2  nr_of_partially_published_docs=3  nr_of_unpublished_docs=0
  VAR  @{document_ids} =  3001  3002  3003  3004  3009
  Verify Inquiry Documents  ${document_ids}
  Go Back
  Open Inquiry  2024-02
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2  nr_of_unpublished_docs=1
  VAR  @{document_ids} =  3007  3008  3009
  Verify Inquiry Documents  ${document_ids}
  Go Back
  Open Inquiry  2024-03
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2
  VAR  @{document_ids} =  3003  3004  3007
  Verify Inquiry Documents  ${document_ids}

Manually Link Inquiry To Decision
  [Documentation]  Create a WooDecision without inquiries and manually link the decision
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport2.xlsx
  ...  documents=files/inquiries/documenten2.zip
  ...  number_of_documents=3
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Decision Linking
  Link Inquiry To Decision  2023-01  ${DOSSIER_REFERENCE}
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2023-01
  Verify Inquiry Dossiers  ${dossier_ids}

Manually Link Inquiry To Documents
  [Documentation]  Create a WooDecision without inquiries and manually link the documents
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport3.xlsx
  ...  documents=files/inquiries/documenten3.zip
  ...  number_of_documents=3
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Document Linking
  Link Inquiry To Documents  files/inquiries/linking3.xlsx
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2022-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2
  VAR  @{document_ids} =  3201  3202  3203
  Verify Inquiry Documents  ${document_ids}

Production Report Inquiry Does Not Unlink
  [Documentation]  Unlinking using a production report should not be possible
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport5.xlsx
  ...  documents=files/inquiries/documenten5.zip
  ...  number_of_documents=2
  Click Publications
  Click Publication By Value  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  files/inquiries/productierapport5-unlinked.xlsx  ${TRUE}
  Verify Production Report Replace  Het nieuwe productierapport is gelijk aan het huidige rapport

Manual Links Are Not Overwritten When Reuploading Production Report
  [Documentation]  Reuploading the original production report after manually linking documents should not be possible.
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport6.xlsx
  ...  documents=files/inquiries/documenten6.zip
  ...  number_of_documents=2
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Document Linking
  Link Inquiry To Documents  files/inquiries/linking6.xlsx
  Open Inquiry  2020-01
  VAR  @{document_ids} =  3501  3502
  Verify Inquiry Documents  ${document_ids}
  Go To Admin
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  files/inquiries/productierapport6.xlsx  ${TRUE}
  Verify Production Report Replace  Het nieuwe productierapport is gelijk aan het huidige rapport


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout

Verify That Inventory Doesn't Contain Concept Docs
  [Arguments]  ${concept_doc_ids}
  Wait For Queue To Empty
  Click Inquiries
  Open Inquiry  2019-01
  ${inventory_file} =  Generic Download Click  //*[@data-e2e-name="download-inventory"]
  Open Excel Document  ${inventory_file}  inventory
  ${column} =  Read Excel Column  col_num=1  row_offset=0  max_num=10
  FOR  ${id}  IN  @{concept_doc_ids}
    IF  ${id} in ${column}
      Fail  The inventory incorrectly contains a document (${id}) from a Concept dossier.
    END
  END
  Close Current Excel Document

Publish The Concept Dossier
  [Arguments]  ${dossier_reference}
  Go To Admin
  Search For A Publication  ${dossier_reference}
  Click  //*[@data-e2e-name="edit-link"]
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
