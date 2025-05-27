*** Settings ***
Documentation       Resource file containing custom keywords for the Complaint Judgement information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Library             OperatingSystem


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Complaint Judgement Details
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Complaint Judgement  ${FILE_LOCATION}
  Click  "Opslaan en verder"

Upload Complaint Judgement
  [Arguments]  ${complaint_judgement_location}
  ${path}  ${file_name} =  Split Path  ${complaint_judgement_location}
  Click  " Klachtoordeel toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${complaint_judgement_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Klachtoordeel '${file_name}' is toegevoegd.
