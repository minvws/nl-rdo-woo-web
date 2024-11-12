*** Settings ***
Library     Browser
Library     DebugLibrary


*** Variables ***
${COMPLAINT_JUDGEMENT_FILENAME}     ${EMPTY}
${COMPLAINT_JUDGEMENT_LOCATION}     ${EMPTY}


*** Keywords ***
Fill Out Complaint Judgement Details
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Complaint Judgement
  Click  "Opslaan en verder"

Upload Complaint Judgement
  Click  " + Klachtoordeel toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${COMPLAINT_JUDGEMENT_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${COMPLAINT_JUDGEMENT_FILENAME}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Klachtoordeel toegevoegd.
