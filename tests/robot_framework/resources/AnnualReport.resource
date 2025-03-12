*** Settings ***
Documentation       Resource file containing custom keywords for the Annual Report information category
Library             Browser
Library             DebugLibrary
Resource            Admin.resource


*** Variables ***
${ANNUAL_REPORT_FILENAME}   ${EMPTY}
${ANNUAL_REPORT_LOCATION}   ${EMPTY}


*** Keywords ***
Fill Out Annual Report Details
  [Arguments]  ${has_attachment}
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Annual Report
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Annual Report
  Click  " + Jaarplan of jaarverslag toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ANNUAL_REPORT_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${ANNUAL_REPORT_FILENAME}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  jaarplan
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Jaarplan toegevoegd.
