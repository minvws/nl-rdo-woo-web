*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests that the documents of a published WooDecision can be found on the public search, by their
...                 content and metadata. Every search is scoped to the dossier published by this suite, so dossiers
...                 from earlier runs containing the same search words don't affect the results.
Resource            ../../resources/Dossier.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/TestData.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Take Screenshot
Test Tags           ci  public  search-content


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}
${DOSSIER_SEARCH_URL}   ${EMPTY}
&{DOC_IDS}              &{EMPTY}


*** Test Cases ***
Text Content Of Pdf, Word And Excel Files Is Found
  [Template]  Search In Dossier Should Find Document
  zwieberkroket  7001
  flinterpudding  7002
  grompelsoep  7003

Ocr Content Of Image-Only Pdfs Is Found
  [Documentation]  These PDFs have no text layer, so their words can only end up in the search index through OCR.
  ...  Document 7005 only has its word on page 2, which verifies that every page is processed.
  [Template]  Search In Dossier Should Find Document
  plofzwemtrompet  7004
  snorkelbaktomaat  7005

Document Is Found By Its Document Id
  Search In Dossier Should Find Document  ${DOC_IDS}[7001]  7001

Content Of A Withdrawn Document Is No Longer Found
  Search In Dossier Should Find Document  verdwijnpoffertje  7006
  Go To Admin
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  ${DOC_IDS}[7006]
  Retract Document
  Wait Until Keyword Succeeds  5min  5s  Dossier Search Should Find Nothing  verdwijnpoffertje


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/searchcontent/productierapport.xlsx
  ...  files/searchcontent/documenten.zip
  VAR  &{DOC_IDS} =  &{doc_ids}  scope=suite
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=6
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Click Search Through Documents In Dossier
  ${search_url} =  Get Url
  VAR  ${DOSSIER_SEARCH_URL} =  ${search_url}  scope=suite

Suite Teardown
  No-Click Logout
  Close Browser

Search In Dossier Should Find Document
  [Documentation]  Retries until the document is indexed, instead of waiting for all queues to empty, as parallel
  ...  suites keep the queues busy.
  [Arguments]  ${search_term}  ${original_document_id}
  Wait Until Keyword Succeeds  5min  5s
  ...  Dossier Search Should Find Only Document  ${search_term}  ${DOC_IDS}[${original_document_id}]

Dossier Search Should Find Only Document
  [Arguments]  ${search_term}  ${document_id}
  Go To  ${DOSSIER_SEARCH_URL}
  Initiate Search  ${search_term}
  Verify Search Result Count  1 resultaat
  Get Text  //li[@data-e2e-name="search-result"]  contains  ${document_id}

Dossier Search Should Find Nothing
  [Arguments]  ${search_term}
  Go To  ${DOSSIER_SEARCH_URL}
  Initiate Search  ${search_term}
  Get Text  //div[@id="js-search-results"]  contains  0 resultaten
