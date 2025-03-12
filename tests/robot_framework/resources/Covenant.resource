*** Settings ***
Documentation       Resource file containing custom keywords for the Covenant information category
Library             Browser
Library             DebugLibrary
Resource            Admin.resource


*** Variables ***
${COVENANT_FILENAME}    ${EMPTY}
${COVENANT_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Covenant Details
  [Arguments]  ${has_attachment}
  Select First Party, Option 1
  Select Second Party, Option 1
  Fill Text  id=content_form_summary  Dummy toelichting
  Upload Covenant
  IF  ${has_attachment}  Upload Attachment  staatsblad
  Click  "Opslaan en verder"

Select First Party, Option 1
  Click  //input[@name="content_form[parties][0]"]
  Click  //form[@name="content_form"]/div[1]/div/fieldset/div[2]/div/div/div/ul/li[1]

Select Second Party, Option 1
  Click  //input[@name="content_form[parties][1]"]
  Click  //form[@name="content_form"]/div[1]/div/fieldset/div[3]/div/div/div/ul/li[1]

Upload Covenant
  Click  " + Convenant toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${COVENANT_LOCATION}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${COVENANT_FILENAME}  timeout=10s
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Convenant toegevoegd.
