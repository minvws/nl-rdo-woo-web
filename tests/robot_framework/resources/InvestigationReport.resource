*** Settings ***
Library     Browser
Library     DebugLibrary


*** Variables ***
${INVESTIGATION_REPORT_FILENAME}    ${EMPTY}
${INVESTIGATION_REPORT_LOCATION}    ${EMPTY}
${ATTACHMENT_FILENAME}              ${EMPTY}
${ATTACHMENT_LOCATION}              ${EMPTY}


*** Keywords ***
Fill Out Investigation Report Details
  [Arguments]  ${has_attachment}
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Investigation Report
  IF  ${has_attachment}  Upload Attachment
  Click  "Opslaan en verder"

Upload Investigation Report
  Click  " + Onderzoeksrapport toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${INVESTIGATION_REPORT_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${INVESTIGATION_REPORT_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  onderzoeksrapport
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Onderzoeksrapport toegevoegd.

Upload Attachment
  Click  " + Bijlage toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ATTACHMENT_LOCATION}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${ATTACHMENT_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  brief
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Bijlage '${ATTACHMENT_FILENAME}' is toegevoegd.
