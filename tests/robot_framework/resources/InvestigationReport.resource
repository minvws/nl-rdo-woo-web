*** Settings ***
Documentation       Resource file containing custom keywords for the Investigation Report information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource


*** Variables ***
${FILE_NAME}        ${EMPTY}
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Investigation Report Details
  [Arguments]  ${has_attachment}
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=content_form_summary  ${random_summary}
  Upload Investigation Report  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  brief
  Click  "Opslaan en verder"

Upload Investigation Report
  [Arguments]  ${investigation_report_location}
  ${_}  ${file_name} =  Split Path  ${investigation_report_location}
  Click  " Onderzoeksrapport toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${investigation_report_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}
  Select Options By  //dialog[@open]//select[@name="type"]  text  onderzoeksrapport
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition
  ...  Text
  ...  //*[@id="inhoud"]
  ...  contains
  ...  Onderzoeksrapport '${file_name}' is toegevoegd.

Get Investigation Report Attachment Types
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
  ...  c_3d782f30
  ...  c_3eb5572a
  ...  c_42e406dd
  ...  c_45be34e9
  ...  c_4f50ca9c
  ...  c_566a4430
  ...  c_5824891d
  ...  c_5b1055aa
  ...  c_61e3099a
  ...  c_6d494ab6
  ...  c_7652d853
  ...  c_7eba29ad
  ...  c_9376c730
  ...  c_97f44ea5
  ...  c_9ecc0007
  ...  c_a17ef403
  ...  c_a1dae55d
  ...  c_a40458df
  ...  c_a55b7649
  ...  c_a6f44748
  ...  c_acb44d77
  ...  c_aebfec50
  ...  c_bf0f9c95
  ...  c_c1956ef0
  ...  c_c2f56984
  ...  c_d4a4792f
  ...  c_d506b718
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
  ...  ww_a2azo7q
  ...  ww_jc6woe9
  RETURN  ${attachment_types}
