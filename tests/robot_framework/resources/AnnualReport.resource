*** Settings ***
Documentation       Resource file containing custom keywords for the Annual Report information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource

*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Annual Report Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Annual Report  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Annual Report
  [Arguments]  ${annual_report_location}
  ${path}  ${file_name} =  Split Path  ${annual_report_location}
  Click  " Jaarplan of jaarverslag toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${annual_report_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  jaarplan
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition
  ...  Text
  ...  //*[@id="inhoud"]
  ...  contains
  ...  Jaarplan of jaarverslag '${file_name}' is toegevoegd.
