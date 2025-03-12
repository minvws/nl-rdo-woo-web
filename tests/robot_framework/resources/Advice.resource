*** Settings ***
Library     Browser
Library     DebugLibrary


*** Variables ***
${ADVICE_FILENAME}  ${EMPTY}
${ADVICE_LOCATION}  ${EMPTY}


*** Keywords ***
Fill Out Advice Details
  Fill Text  id=content_form_summary  Regression Tests toelichting
  Upload Advice
  Click  "Opslaan en verder"

Upload Advice
  Click  " + Advies toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ADVICE_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${ADVICE_FILENAME}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Advies toegevoegd.
