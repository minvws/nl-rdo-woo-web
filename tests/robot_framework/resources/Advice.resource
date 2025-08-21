*** Settings ***
Documentation       Resource file containing custom keywords for the Advice information category
Library             OperatingSystem
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Advice Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Advice  ${FILE_LOCATION}
  Upload Advice Request  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Advice
  [Arguments]  ${advice_location}
  ${_}  ${file_name} =  Split Path  ${advice_location}
  Click  " Advies toevoegen... "
  Wait Until Keyword Succeeds  3x  5s  Upload And Submit  ${file_name}  ${advice_location}
  Success Alert Is Visible
  ...  contains_message=Advies '${file_name}' is toegevoegd.
  ...  selector=//*[@data-e2e-name="main-document"][1]//*[@data-e2e-name="alerts"]/div[contains(@class, 'bhr-alert--success')]

Upload Advice Request
  [Arguments]  ${advice_request_location}
  ${_}  ${file_name} =  Split Path  ${advice_request_location}
  Click  " Adviesaanvraag toevoegen... "
  Wait Until Keyword Succeeds  3x  5s  Upload And Submit  ${file_name}  ${advice_request_location}
  Success Alert Is Visible
  ...  contains_message=Adviesaanvraag '${file_name}' is toegevoegd.
  ...  selector=//*[@data-e2e-name="attachments"][1]//*[@data-e2e-name="alerts"]/div[contains(@class, 'bhr-alert--success')]

Upload And Submit
  [Arguments]  ${file_name}  ${advice_location}
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${advice_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${file_name}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Get Element Count  //dialog[@open]//*[@data-e2e-name="save-failed"]  should be  0
