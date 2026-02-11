*** Comments ***
# robocop: off=no-suite-variable,line-too-long


*** Settings ***
Documentation       Resource file containing custom keywords for Decision Dossiers in the Balie.
Library             DateTime
Library             Browser
Library             DebugLibrary
Resource            Admin.resource
Resource            Public.resource
Resource            WooDecision.resource


*** Variables ***
${CURRENT_TIME}             ${EMPTY}
${CURRENT_DATE}             ${EMPTY}
${CURRENT_DATE_FORMAT2}     ${EMPTY}
${CURRENT_EPOCH}            ${EMPTY}
${PREFIX_INDEX}             ${EMPTY}


*** Keywords ***
Fill Out Basic Details
  [Arguments]  ${date_from}=2021-12-01T00:00:00+00:00
  ...  ${date_to}=2023-01-31T00:00:00+00:00
  ...  ${type}=${EMPTY}
  ...  ${prefix}=E2E-A
  Generate Dossier Reference Number
  Fill Text  id=details_title  Robot ${DOSSIER_REFERENCE}
  IF  '${type}' == 'disposition' or '${type}' == 'investigation-report' or '${type}' == 'complaint-judgement' or '${type}' == 'other-publication' or '${type}' == 'advice' or '${type}' == 'request-for-advice'
    Type Text  id=details_date  01012022
  ELSE IF  '${type}' == 'annual-report'
    Select Options By  id=details_year  index  5
  ELSE
    Select Options By  id=details_date_from  value  ${date_from}
    Select Options By  id=details_date_to  value  ${date_to}
  END
  Select Options By  //select[@name="details[departments][0]"]  index  1
  IF  '${type}' == 'woo-decision'
    Check Checkbox  id=details_publication_reason_1
  END
  Select Random Subject
  Select Options By  id=details_documentPrefix_documentPrefix  text  ${prefix}
  Fill Text  id=details_dossierNr  ${DOSSIER_REFERENCE}
  Click  "Opslaan en verder"

Select Random Subject
  ${nr_of_subjects} =  Get Element Count  //select[@name="details[subject]"]/option
  ${random_number} =  Evaluate  random.randint(1, ${nr_of_subjects})  random
  ${random_index} =  Evaluate  ${random_number} - 1
  Select Options By  //select[@name="details[subject]"]  index  ${random_index}

Generate Dossier Reference Number
  ${reference_number} =  Generate Random String  10  [NUMBERS][UPPER]
  VAR  ${DOSSIER_REFERENCE} =  ${reference_number}  scope=suite
  RETURN  ${DOSSIER_REFERENCE}

Search For A Publication
  [Documentation]  Clicks the link with matching text, if multiple are present it will fail.
  [Arguments]  ${search_query}
  ${search_query_lowercase} =  Convert To Lower Case  ${search_query}
  Type Text  //div[@data-e2e-name="dossier-search"]//input  ${search_query_lowercase}  delay=50 ms  clear=${TRUE}
  Click
  ...  //div[@data-e2e-name="search-previews-results"]//table[@data-e2e-name="table-dossier"]//a[contains(@href,'${search_query_lowercase}')]

Verify Admin Search Results
  [Arguments]  ${search_query}  ${expexted_nr_of_results}
  ${search_query_lowercase} =  Convert To Lower Case  ${search_query}
  Type Text  //div[@data-e2e-name="dossier-search"]//input  ${search_query_lowercase}  delay=50 ms  clear=${TRUE}
  Get Element Count  //div[@data-e2e-name="search-previews-results"]//tbody/tr  should be  ${expexted_nr_of_results}

Click Documents Edit
  Click  //*[@data-e2e-name="documents-section"]//*[@data-e2e-name="edit-link"]

Documents Edit Button Should Not Exist
  Get Element Count  //*[@data-e2e-name="documents-section"]//*[@data-e2e-name="edit-link"]  equal  0

Create New Dossier
  [Arguments]  ${dossier_type}
  Click  //*[@data-e2e-name="create-dossier"]
  Choose Dossier Type  ${dossier_type}

Choose Dossier Type
  [Documentation]  Check if there is an option to choose Convenant or Woo -- This is only shown when you have super-admin permissions.
  [Arguments]  ${dossier_type}=woo-decision
  Click  //*[@data-e2e-name="create-dossier-type-${dossier_type}"]

Publish Dossier And Return To Admin Home
  [Arguments]  ${publication_status}=Gepubliceerd
  IF  "${publication_status}" == "Gepubliceerd"
    Click Save And Prepare
    Click Publications
  ELSE IF  "${publication_status}" == "Gepland"
    ${timestamp} =  Get Current Date
    ${next_year} =  Add Time To Date  ${timestamp}  730 days
    Fill Publication Date  ${next_year}
    Click Save And Prepare
    Click Publications
  ELSE IF  "${publication_status}" == "Concept"
    Click Publications
  END

Click Publications
  Click  //*[@data-e2e-name="nav-publications"]

Click Publication By Value
  [Arguments]  ${value}
  ${value} =  Convert To Lower Case  ${value}
  Click  //table[@data-e2e-name="dossiers-table"]//td[contains(.,'${value}')]//a

Verify Publication Confirmation
  [Arguments]  ${preview_date}  ${publication_date}
  Get Text  //*[@data-e2e-name="dossier-preview-date"]  contains  ${preview_date}
  Get Text  //*[@data-e2e-name="dossier-publication-date"]  contains  ${publication_date}
  Get Text  //*[@data-e2e-name="dossier-publication-date-link"]  contains  ${preview_date}

Click Search Through Documents In Dossier
  Click  //*[@data-e2e-name="search-through-documents"]

Verify Document Filetype
  [Arguments]  ${document_number}  ${source_type}  ${file_type}
  Click  //div[@data-e2e-name="documents-section"]//tbody/tr[td[contains(.,'${document_number}')]]//td//a
  Get Text  //td[@data-e2e-name="source-type"]  contains  ${source_type}
  Get Text  //td[@data-e2e-name="file-type"]  contains  ${file_type}
  Get Text  //a[@data-e2e-name="download-file-link"]  contains  ${file_type.lower()}
  Generic Download Click  //a[@data-e2e-name="download-file-link"]  ${file_type}
  Go Back

Select First Public Dossier
  Click  (//table[@data-e2e-name="dossiers-table"]/tbody/tr[td[contains(.,'Openbaar')]]//td//a)[1]

Click Continue To Publish
  Wait For Load State  networkidle
  Click  //*[@data-e2e-name="to-next-step-link"]

Click Save And Prepare
  Click With CSRF Retry  //*[@id="publish_submit"]
  Success Alert Is Visible  klaarzetten gelukt: De publicatie wordt voorbereid

Click Edit Details
  Click  //a[@data-e2e-name="edit-details"]

Edit Main Document
  Click  //*[@data-e2e-name="main-document"]//*[@data-e2e-name="edit-file"]

Verify Publication Action Status
  [Arguments]  ${dossier_reference}  ${expected_action}
  Click Publications
  IF  '${expected_action}' != '${EMPTY}'
    Get Text
    ...  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${dossier_reference}')]/td[7]
    ...  contains
    ...  ${expected_action}
  ELSE
    Get Text
    ...  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${dossier_reference}')]/td[7]
    ...  equals
    ...  ${EMPTY}
  END

Verify Dossier Document Count
  [Arguments]
  ...  ${nr_of_published_docs}=0
  ...  ${nr_of_partially_published_docs}=0
  ...  ${nr_of_unpublished_docs}=0
  ...  ${nr_of_not_yet_online_docs}=0
  Get Text  //*[@data-e2e-name="tabs-documenten-button-1"]  contains  (${nr_of_published_docs})
  Get Text  //*[@data-e2e-name="tabs-documenten-button-2"]  contains  (${nr_of_partially_published_docs})
  Get Text  //*[@data-e2e-name="tabs-documenten-button-3"]  contains  (${nr_of_unpublished_docs})
  Get Text  //*[@data-e2e-name="tabs-documenten-button-4"]  contains  (${nr_of_not_yet_online_docs})
