*** Settings ***
Documentation       Resource file containing custom keywords for the Other Publication information category
Library             Browser
Library             DebugLibrary
Resource            Admin.resource
Library             FakerLibrary


*** Variables ***
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Other Publication Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Informatiestuk  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Informatiestuk
  [Arguments]  ${other_publication_location}
  ${_}  ${file_name} =  Split Path  ${other_publication_location}
  Click  " Informatiestuk toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${other_publication_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}
  Select Options By  //dialog[@open]//select[@name="type"]  text  overig
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Informatiestuk '${file_name}' is toegevoegd.

Get Other Publication Attachment Types
  VAR  @{attachment_types} =
  ...  c_c1956ef0
  ...  c_acb44d77
  ...  c_30e8b503
  ...  c_2ab17960
  ...  c_2c0438f4
  ...  c_f7dc55d9
  ...  c_aebfec50
  ...  c_002fc258
  RETURN  ${attachment_types}
