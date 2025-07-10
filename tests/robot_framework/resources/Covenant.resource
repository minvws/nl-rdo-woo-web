*** Settings ***
Documentation       Resource file containing custom keywords for the Covenant information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource
Resource            Dossier.resource
Resource            TestData.resource


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Covenant Details
  [Arguments]  ${has_attachment}=${FALSE}
  Select First Party, Option 1
  Select Second Party, Option 1
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Click  " Convenant toevoegen... "
  Upload Covenant  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  staatsblad
  Click  "Opslaan en verder"

Select First Party, Option 1
  Click  //input[@name="content_form[parties][0]"]
  Click  (//form//ul)[1]/li[@data-e2e-name="combobox-option"][1]

Select Second Party, Option 1
  Click  //input[@name="content_form[parties][1]"]
  Click  (//form//ul)[2]/li[@data-e2e-name="combobox-option"][1]

Upload Covenant
  [Arguments]  ${covenant_location}
  ${_}  ${file_name} =  Split Path  ${covenant_location}
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${covenant_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${file_name}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Convenant '${file_name}' is

Publish Test Covenant
  [Arguments]  ${publication_status}=Gepubliceerd  ${has_attachment}=${FALSE}
  VAR  ${type} =  covenant
  Create New Dossier  ${type}
  Generate Test Data Set  ${type}  ${has_attachment}
  Fill Out Basic Details  type=${type}
  Fill Out Covenant Details  ${has_attachment}
  Publish Dossier And Return To Admin Home  ${publication_status}
  Log  Dossier reference: ${DOSSIER_REFERENCE}
