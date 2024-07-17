*** Settings ***
Library     Browser
Library     DebugLibrary


*** Variables ***
${ANNUAL_REPORT_FILENAME}   ${EMPTY}
${ANNUAL_REPORT_LOCATION}   ${EMPTY}
${ATTACHMENT_FILENAME}      ${EMPTY}
${ATTACHMENT_LOCATION}      ${EMPTY}


*** Keywords ***
Fill Out Annual Report Details
  [Arguments]  ${has_attachment}
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Annual Report
  IF  ${has_attachment}  Upload Attachment
  Click  "Opslaan en verder"

Upload Annual Report
  Click  " + Jaarplan of jaarverslag toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ANNUAL_REPORT_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${ANNUAL_REPORT_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  jaarplan
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Jaarplan toegevoegd.

Upload Attachment
  Click  " + Bijlage toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ATTACHMENT_LOCATION}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${ATTACHMENT_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  brief
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Bijlage '${ATTACHMENT_FILENAME}' is toegevoegd.
