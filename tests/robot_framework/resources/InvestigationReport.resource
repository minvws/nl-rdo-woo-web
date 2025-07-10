*** Settings ***
Documentation       Resource file containing custom keywords for the Investigation Report information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Investigation Report Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Investigation Report  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Investigation Report
  [Arguments]  ${investigation_report_location}
  ${_}  ${file_name} =  Split Path  ${investigation_report_location}
  Click  " Onderzoeksrapport toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${investigation_report_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  onderzoeksrapport
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition
  ...  Text
  ...  //*[@id="inhoud"]
  ...  contains
  ...  Onderzoeksrapport '${file_name}' is toegevoegd.
