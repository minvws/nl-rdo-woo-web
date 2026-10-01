*** Settings ***
Documentation       Tests that focus on using the inquiry system.
...                 Split into Inquiries.robot and InquiriesManualLinking.robot to keep
...                 individual pabot suites balanced.
Resource            ../../resources/Inquiry.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  inquiries


*** Test Cases ***
Preview Inquiry Access
  [Documentation]  Verify preview access to a dossier using inquiry page
  Click Publications
  ${case_id} =  FakerLibrary.Uuid 4
  Generate Test Data Set  woo-decision  case_id=${case_id}
  ${doc_name} =  FakerLibrary.Uuid 4
  ${doc_name} =  Catenate  ${doc_name} .docx
  Modify Production Report  ${PRODUCTION_REPORT}  2  5  ${doc_name}
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  ...  publication_status=Gepland
  Wait For Queue To Empty
  # Verify the document can be found through the inquiry
  Click Inquiries
  Open Inquiry  ${case_id}
  Click Search Through Documents In Inquiry
  Verify Search Result Count  5 resultaten in 1 publicatie
  Initiate Search  ${doc_name}
  Verify Search Result Count  1 resultaat in 1 publicatie
  Click  "${doc_name}"
  ${document_url} =  Get URL
  # Verify the document can't be found normally
  New Context  locale=nl-NL
  New Page
  Go To Public
  Search On Public For  ${doc_name}  0 resultaten
  # Verify the document page can't be accessed normally
  Go To  ${document_url}
  Verify Page Error  404

Inquiry With Multiple Dossiers
  [Documentation]  Create an inquiry with multiple dossiers
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport7a.xlsx
  ...  documents=files/inquiries/documenten7a.zip
  ...  number_of_documents=9
  ...  publication_status=Concept
  VAR  ${concept_dossier} =  ${DOSSIER_REFERENCE}
  VAR  ${dossier1} =  ${DOSSIER_REFERENCE}
  VAR  @{concept_doc_ids} =  3601  3602
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport7b.xlsx
  ...  documents=files/inquiries/documenten7b.zip
  ...  number_of_documents=3
  VAR  ${dossier2} =  ${DOSSIER_REFERENCE}
  Verify That Inventory Doesn't Contain Concept Docs  ${concept_doc_ids}
  Publish The Concept Dossier  ${concept_dossier}
  Click Inquiries
  Open Inquiry  2019-01
  ${ids} =  Evaluate  [3601, 3602, 3603, 3604, 3609],[],[3610],[]
  Verify Inquiry Dossier  ${dossier1}  ${ids}
  ${ids} =  Evaluate  [3611, 3613],[],[],[]
  Verify Inquiry Dossier  ${dossier2}  ${ids}

Verify Download Of Full Inquiry
  [Documentation]  Create a test dossier for an inquiry and then donwload the full inquiry
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport8.xlsx
  ...  documents=files/inquiries/documenten8.zip
  ...  number_of_documents=5
  ...  publication_status=Gepland
  Click Inquiries
  Open Inquiry  8000-01
  Click First Dossier In Inquiry
  Download Inquiry Archive

Link Inquiries Using Production Report
  [Documentation]  Create a WooDecision that is part of multiple inquiries using Production Report upload
  Publish Test WooDecision
  ...  production_report=files/inquiries/productierapport1.xlsx
  ...  documents=files/inquiries/documenten1.zip
  ...  number_of_documents=9
  Click Inquiries
  Open Inquiry  2024-01
  ${ids} =  Evaluate  [3001, 3002, 3003, 3004, 3009],[],[3010],[]
  Verify Inquiry Dossier  ${DOSSIER_REFERENCE}  ${ids}
  Go To Admin
  Click Inquiries
  Open Inquiry  2024-02
  ${ids} =  Evaluate  [3007, 3008, 3009],[],[3010],[]
  Verify Inquiry Dossier  ${DOSSIER_REFERENCE}  ${ids}
  Go To Admin
  Click Inquiries
  Open Inquiry  2024-03
  ${ids} =  Evaluate  [3003, 3004, 3007],[],[],[]
  Verify Inquiry Dossier  ${DOSSIER_REFERENCE}  ${ids}

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


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  ${_}  ${organisation_name} =  Create New Organisation
  Select Organisation  ${organisation_name}

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
  Click Documents Edit
  Click Continue To Publish
  Publish Dossier And Return To Admin Home

Modify Production Report
  [Arguments]  ${production_report}  ${row_num}  ${col_num}  ${value}
  Open Excel Document  ${production_report}  prodrep
  Write Excel Cell  ${row_num}  ${col_num}  ${value}
  Save Excel Document  ${production_report}
  Close All Excel Documents
