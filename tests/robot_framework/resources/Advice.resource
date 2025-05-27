*** Settings ***
Library     Browser
Library     DebugLibrary
Library     OperatingSystem


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Advice Details
  Fill Text  id=content_form_summary  Regression Tests toelichting
  Upload Advice  ${FILE_LOCATION}
  Click  "Opslaan en verder"

Upload Advice
  [Arguments]  ${advice_location}
  ${path}  ${file_name} =  Split Path  ${advice_location}
  Click  " Advies toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${advice_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Advies '${file_name}' is toegevoegd.
