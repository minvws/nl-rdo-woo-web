*** Settings ***
Documentation       Resource file containing custom keywords for the Other Publication information category
Library             Browser
Library             DebugLibrary


*** Variables ***
${OTHER_PUBLICATION_FILENAME}   ${EMPTY}
${OTHER_PUBLICATION_LOCATION}   ${EMPTY}


*** Keywords ***
Fill Out Other Publication Details
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Informatiestuk
  Click  "Opslaan en verder"

Upload Informatiestuk
  Click  " + Informatiestuk toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${OTHER_PUBLICATION_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${OTHER_PUBLICATION_FILENAME}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  overig
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Overig toegevoegd.
