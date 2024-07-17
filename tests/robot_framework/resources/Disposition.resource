*** Settings ***
Library     Browser
Library     DebugLibrary
Resource    Admin.resource


*** Variables ***
${DISPOSITION_FILENAME}     ${EMPTY}
${DISPOSITION_LOCATION}     ${EMPTY}
${ATTACHMENT_FILENAME}      ${EMPTY}
${ATTACHMENT_LOCATION}      ${EMPTY}


*** Keywords ***
Fill Out Disposition Details
  [Arguments]  ${has_attachment}
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Disposition
  IF  ${has_attachment}  Upload Attachment  staatsblad
  Click  "Opslaan en verder"

Upload Disposition
  Click  " + Beschikking toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${DISPOSITION_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${DISPOSITION_FILENAME}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  concessie
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Concessie toegevoegd.
