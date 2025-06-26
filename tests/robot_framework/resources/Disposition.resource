*** Settings ***
Documentation       Resource file containing custom keywords for the Disposition information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource

*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Disposition Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Disposition  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  staatsblad
  Click  "Opslaan en verder"

Upload Disposition
  [Arguments]  ${disposition_location}
  ${path}  ${file_name} =  Split Path  ${disposition_location}
  Click  " Beschikking toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${FILE_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  concessie
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Beschikking '${file_name}' is toegevoegd.
