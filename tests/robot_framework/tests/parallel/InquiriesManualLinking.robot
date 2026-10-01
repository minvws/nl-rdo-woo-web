*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Manual-linking and larger-scale inquiry tests.
...                 Split out of Inquiries.robot to keep individual pabot suites balanced.
Resource            ../../resources/Inquiry.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  inquiries


*** Variables ***
${NEW_PREFIX}   ${EMPTY}


*** Test Cases ***
Manually Link Inquiry To Documents
  [Documentation]  Create a WooDecision without inquiries and manually link the documents
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport3.xlsx
  ...  documents=files/inquiries/documenten3.zip
  ...  number_of_documents=3
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Document Linking
  Link Inquiry To Documents  files/inquiries/linking3.xlsx  ${NEW_PREFIX}  expect_matter_notice=${TRUE}
  Open Inquiry  2022-01
  ${ids} =  Evaluate  [3201, 3202, 3203],[],[],[]
  Verify Inquiry Dossier  ${DOSSIER_REFERENCE}  ${ids}

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
  Link Inquiry To Documents  files/inquiries/linking6.xlsx  ${NEW_PREFIX}
  Open Inquiry  2020-01
  ${ids} =  Evaluate  [3501, 3502],[],[],[]
  Verify Inquiry Dossier  ${DOSSIER_REFERENCE}  ${ids}
  Go To Admin
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  files/inquiries/productierapport6.xlsx  ${TRUE}
  Verify Production Report Replace  Het nieuwe productierapport is gelijk aan het huidige rapport

Large Inquiry
  Click Publications
  Set Browser Timeout  5min
  WHILE  True  limit=11  on_limit=pass
    VAR  ${nr_of_documents} =  1
    ${test_data_location} =  Generate Test Documents  ${nr_of_documents}
    Create Zip From Files In Directory  ${test_data_location}  filename=${test_data_location}/Archive.zip
    ${production_report_location} =  Create Test Production Report  ${test_data_location}  2025-09
    Publish Test WooDecision
    ...  production_report=${production_report_location}
    ...  documents=${test_data_location}/Archive.zip
    ...  number_of_documents=${nr_of_documents}
  END
  Set Browser Timeout  30s
  Click Inquiries
  Open Inquiry  2025-09
  Get Element Count  //*[@data-e2e-name="inquiry-dossiers"]//tbody//tr  should be  10
  Get Element Count
  ...  //*[@data-e2e-name="inquiry-dossiers"]//tbody//td[contains(.,'1 documenten in dit besluit')]
  ...  should be
  ...  10
  # Check history
  Scroll To Element  (//*[@data-e2e-name="document-history"])
  Get Element Count
  ...  //*[@data-e2e-name="document-history"]//*[@data-e2e-name="document-history-action"][contains(.,'1 document(en) toegevoegd')]
  ...  equals
  ...  5
  Click  //*[@data-e2e-name="show-full-history"]
  Get Element Count
  ...  (//*[@data-e2e-name="document-history-follow-up"])//*[@data-e2e-name="document-history-action"][contains(.,'1 document(en) toegevoegd')]
  ...  equals
  ...  6
  Click  //*[@data-e2e-name="view-all-dossiers"]
  Get Element Count  //*[@data-e2e-name="inquiry-dossiers"]//tbody//tr  should be  11
  Get Element Count
  ...  //*[@data-e2e-name="inquiry-dossiers"]//tbody//td[contains(.,'1 documenten in dit besluit')]
  ...  should be
  ...  11

Replacing Production Report Updates Document Names In Inventory Pages
  [Documentation]  Create a WooDecision with an inquiry, replace the production report with
  ...              updated document names, and verify that the inventory on the public dossier
  ...              page and on the public inquiry dossier page are both updated.
  Click Publications
  ${case_id} =  FakerLibrary.Uuid 4
  Generate Test Data Set  woo-decision  case_id=${case_id}
  ${old_doc_name} =  FakerLibrary.Sentence  nb_words=4
  ${old_doc_name} =  Catenate  ${old_doc_name}txt
  Modify Production Report  ${PRODUCTION_REPORT}  2  5  ${old_doc_name}
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  Wait For Queue To Empty
  # Update the production report file with a new document name
  ${new_doc_name} =  FakerLibrary.Sentence  nb_words=4
  ${new_doc_name} =  Catenate  ${new_doc_name}txt
  Modify Production Report  ${PRODUCTION_REPORT}  2  5  ${new_doc_name}
  Replace The Production Report On The Published Dossier
  Verify Updated Document Name In Public Inventory  ${new_doc_name}  ${old_doc_name}
  Verify Updated Document Name In Inquiry Inventory  ${new_doc_name}  ${old_doc_name}  ${case_id}


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  ${prefix}  ${organisation_name} =  Create New Organisation
  Select Organisation  ${organisation_name}
  VAR  ${NEW_PREFIX} =  ${prefix}  scope=suite

Suite Teardown
  No-Click Logout

Modify Production Report
  [Arguments]  ${production_report}  ${row_num}  ${col_num}  ${value}
  Open Excel Document  ${production_report}  prodrep
  Write Excel Cell  ${row_num}  ${col_num}  ${value}
  Save Excel Document  ${production_report}
  Close All Excel Documents

Replace The Production Report On The Published Dossier
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  ${PRODUCTION_REPORT}  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  1 bestaand document wordt aangepast.
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents

Verify Updated Document Name In Public Inventory
  [Arguments]  ${new_doc_name}  ${old_doc_name}
  Go To Admin
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  ${inventory_file} =  Download WooDecision Inventory
  Open Excel Document  ${inventory_file}  inventory
  ${names_column} =  Read Excel Column  col_num=3  row_offset=0  max_num=10
  Should Contain  ${names_column}  ${new_doc_name}
  Should Not Contain  ${names_column}  ${old_doc_name}
  Close Current Excel Document

Verify Updated Document Name In Inquiry Inventory
  [Arguments]  ${new_doc_name}  ${old_doc_name}  ${case_id}
  Wait For Queue To Empty
  Go To Admin
  Click Inquiries
  Open Inquiry  ${case_id}
  ${inventory_file} =  Generic Download Click  //*[@data-e2e-name="download-inventory"]
  Open Excel Document  ${inventory_file}  inventory
  ${names_column} =  Read Excel Column  col_num=3  row_offset=0  max_num=10
  Should Contain  ${names_column}  ${new_doc_name}
  Should Not Contain  ${names_column}  ${old_doc_name}
  Close Current Excel Document
