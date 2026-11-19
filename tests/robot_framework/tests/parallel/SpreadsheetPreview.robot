*** Settings ***
Documentation       Tests for the spreadsheet preview of xlsx main documents on the public site.
Resource            ../../resources/ComplaintJudgement.resource
Resource            ../../resources/Disposition.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      No-Click Logout
Test Setup          Go To Admin
Test Tags           ci  spreadsheetpreview


*** Variables ***
${SMALL_SPREADSHEET}        files/spreadsheetpreview/klachtoordelen.xlsx
${LARGE_SPREADSHEET}        files/spreadsheetpreview/beschikkingen-groot.xlsx
${PREVIEW}                  //*[@data-e2e-name="spreadsheet-preview"]
${SEARCH_RESULT}            ${PREVIEW}//*[@data-e2e-name="spreadsheet-search-result"]
${TRUNCATED_NOTICE}         ${PREVIEW}//*[@data-e2e-name="spreadsheet-truncated"]
${VISIBLE_ROWS}             ${PREVIEW}//tbody/tr[not(@hidden)]


*** Test Cases ***
Complaint Judgement Spreadsheet Can Be Sorted And Searched On Public
  Publish Complaint Judgement With Main Document  ${SMALL_SPREADSHEET}
  Open Main Document On Public  klachtoordelen.xlsx
  Get Text  ${PREVIEW}//thead  contains  Naam
  Get Element Count  ${VISIBLE_ROWS}  ==  4
  Get Element Count  ${TRUNCATED_NOTICE}  ==  0
  Sort Spreadsheet By Column  Bedrag
  Verify Spreadsheet Sort  Bedrag  ascending  30
  Sort Spreadsheet By Column  Bedrag
  Verify Spreadsheet Sort  Bedrag  descending  1200
  Search In Spreadsheet  Amsterdam
  Get Element Count  ${VISIBLE_ROWS}  ==  2
  Get Text  ${SEARCH_RESULT}  ==  2 van de 4 rijen getoond voor de zoekterm "Amsterdam"
  Search In Spreadsheet  Groningen
  Get Element Count  ${VISIBLE_ROWS}  ==  0
  Get Text  ${SEARCH_RESULT}  ==  Geen rijen gevonden voor de zoekterm "Groningen"
  Search In Spreadsheet  ${EMPTY}
  Get Element Count  ${VISIBLE_ROWS}  ==  4

Disposition Spreadsheet Above Row Limit Shows Truncated Preview On Public
  Publish Disposition With Main Document  ${LARGE_SPREADSHEET}
  Open Main Document On Public  beschikkingen-groot.xlsx
  Get Element Count  ${VISIBLE_ROWS}  ==  100
  Get Text  ${TRUNCATED_NOTICE}  contains  We tonen hier maximaal 100 rijen en 3 kolommen.


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Publish Complaint Judgement With Main Document
  [Arguments]  ${file_location}
  Click Publications
  Create New Dossier  complaint-judgement
  Fill Out Basic Details  type=complaint-judgement
  Fill Out Summary
  Upload Complaint Judgement  ${file_location}
  Click Save And Continue
  Publish Dossier And Return To Admin Home

Publish Disposition With Main Document
  [Arguments]  ${file_location}
  Click Publications
  Create New Dossier  disposition
  Fill Out Basic Details  type=disposition
  Fill Out Summary
  Upload Disposition  ${file_location}
  Click Save And Continue
  Publish Dossier And Return To Admin Home

Open Main Document On Public
  [Arguments]  ${file_name}
  # The preview table is built in the background after upload
  Wait For Queue To Empty
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Click  //a[normalize-space()="${file_name}"]
  Wait For Elements State  ${PREVIEW}//table  visible

Sort Spreadsheet By Column
  [Arguments]  ${column}
  Click  ${PREVIEW}//th[contains(., "${column}")]//button

Verify Spreadsheet Sort
  [Arguments]  ${column}  ${direction}  ${first_value}
  Get Attribute  ${PREVIEW}//th[contains(., "${column}")]  aria-sort  ==  ${direction}
  ${column_index} =  Get Element Count  ${PREVIEW}//th[contains(., "${column}")]/preceding-sibling::th
  ${cell_position} =  Evaluate  ${column_index} + 1
  Get Text  ${PREVIEW}//tbody/tr[1]/td[${cell_position}]  ==  ${first_value}

Search In Spreadsheet
  [Arguments]  ${search_term}
  Wait For Elements State  id=search-document-input  visible
  Fill Text  id=search-document-input  ${search_term}
