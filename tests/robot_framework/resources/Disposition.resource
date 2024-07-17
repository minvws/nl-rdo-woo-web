*** Settings ***
Library     Browser
Library     DebugLibrary


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
  IF  ${has_attachment}  Upload Attachment
  Click  "Opslaan en verder"

Upload Disposition
  Click  " + Beschikking toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${DISPOSITION_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${DISPOSITION_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  concessie
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Concessie toegevoegd.

Upload Attachment
  Click  " + Bijlage toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ATTACHMENT_LOCATION}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${ATTACHMENT_FILENAME}  timeout=5s
  Select Options By  //dialog[@open]//select[@name="type"]  text  staatsblad
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Bijlage '${ATTACHMENT_FILENAME}' is toegevoegd.
