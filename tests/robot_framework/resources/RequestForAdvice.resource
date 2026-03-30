*** Settings ***
Documentation       Resource file containing custom keywords for the Advice REquest information category
Library             OperatingSystem
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource


*** Variables ***
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Request For Advice Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Request For Advice  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Request For Advice
  [Arguments]  ${advice_location}
  ${_}  ${file_name} =  Split Path  ${advice_location}
  Click  " Adviesaanvraag toevoegen... "
  Wait Until Keyword Succeeds  3x  5s  Upload And Submit  ${file_name}  ${advice_location}
  Success Alert Is Visible
  ...  contains_message=Adviesaanvraag '${file_name}' is toegevoegd.
  ...  selector=//*[@data-e2e-name="main-document"][1]//*[@data-e2e-name="alert"][contains(@class, 'bhr-alert--success')]

Upload And Submit
  [Arguments]  ${file_name}  ${advice_location}
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${advice_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${file_name}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Get Element Count  //dialog[@open]//*[@data-e2e-name="save-failed"]  should be  0

Get Request For Advice Attachment Types
  VAR  @{attachment_types} =
  ...  c_002fc258
  ...  c_03c52ba0
  ...  c_056a75e1
  ...  c_0670bae1
  ...  c_06a67c95
  ...  c_0e425c23
  ...  c_1cf18f83
  ...  c_2977c34f
  ...  c_2ab17960
  ...  c_2aedadff
  ...  c_2c0438f4
  ...  c_30e8b503
  ...  c_386e74cb
  ...  c_38ba44de
  ...  c_3d782f30
  ...  c_3eb5572a
  ...  c_42e406dd
  ...  c_45be34e9
  ...  c_4efe1293
  ...  c_4f50ca9c
  ...  c_566a4430
  ...  c_5824891d
  ...  c_5b1055aa
  ...  c_61e3099a
  ...  c_6d494ab6
  ...  c_6f49bf34
  ...  c_7652d853
  ...  c_7eba29ad
  ...  c_8b92eab4
  ...  c_9376c730
  ...  c_97f44ea5
  ...  c_99d3e284
  ...  c_9ecc0007
  ...  c_a17ef403
  ...  c_a1dae55d
  ...  c_a55b7649
  ...  c_a6f44748
  ...  c_acb44d77
  ...  c_aebfec50
  ...  c_bf0f9c95
  ...  c_c1956ef0
  ...  c_c2f56984
  ...  c_cccba364
  ...  c_d4a4792f
  ...  c_d943ca24
  ...  c_dad2a6ed
  ...  c_de27ae7a
  ...  c_df2cb56e
  ...  c_dfa0ff1f
  ...  c_ef935990
  ...  c_f1652921
  ...  c_f7dc55d9
  ...  c_f90465b3
  ...  c_fbaa7e4b
  ...  overig
  ...  ww_a2azo7q
  ...  ww_jc6woe9
  RETURN  ${attachment_types}
