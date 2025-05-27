*** Settings ***
Documentation       Resource file containing custom keywords for the Other Publication information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Other Publication Details
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Informatiestuk  ${FILE_LOCATION}
  Click  "Opslaan en verder"

Upload Informatiestuk
  [Arguments]  ${other_publication_location}
  ${path}  ${file_name} =  Split Path  ${other_publication_location}
  Click  " Informatiestuk toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${other_publication_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  overig
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Informatiestuk '${file_name}' is toegevoegd.
