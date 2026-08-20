*** Settings ***
Documentation       Resource file containing custom keywords for the DraftDecision information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource
Resource            Dossier.resource
Resource            TestData.resource


*** Variables ***
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Publish Test DraftDecision
  [Arguments]  ${publication_status}=Gepubliceerd  ${has_attachment}=${FALSE}
  VAR  ${type} =  draft-decision
  Generate Test Data Set  ${type}  ${has_attachment}
  Create New Dossier  ${type}
  Fill Out Basic Details  type=${type}
  Fill Out DraftDecision Details  ${has_attachment}
  Publish Dossier And Return To Admin Home  ${publication_status}
  Log  Dossier reference: ${DOSSIER_REFERENCE}

Fill Out DraftDecision Details
  [Arguments]  ${has_attachment}=${FALSE}
  Fill Out Summary
  Upload DraftDecision Main Document  ${FILE_LOCATION}
  Upload DraftDecision Required Attachment  ${FILE_LOCATION}
  IF  ${has_attachment}  Upload Attachment  staatsblad
  Click Save And Continue

Upload DraftDecision Main Document
  [Arguments]  ${document_location}
  ${_}  ${file_name} =  Split Path  ${document_location}
  Click  " Ontwerpbesluit toevoegen... "
  Wait Until Keyword Succeeds  3x  5s  Upload And Submit DraftDecision Document  ${file_name}  ${document_location}
  Success Alert Is Visible
  ...  contains_message=Ontwerpbesluit '${file_name}' is toegevoegd.
  ...  selector=//*[@data-e2e-name="main-document"]//*[@data-e2e-name="alert"][contains(@class, 'bhr-alert--success')]

Upload DraftDecision Required Attachment
  [Arguments]  ${attachment_location}  ${type}=adviesaanvraag
  ${_}  ${file_name} =  Split Path  ${attachment_location}
  Click  " Adviesaanvraag of opdrachtformulering toevoegen... "
  Wait Until Keyword Succeeds
  ...  3x
  ...  5s
  ...  Upload And Submit DraftDecision Attachment
  ...  ${file_name}
  ...  ${attachment_location}
  ...  ${type}
  Success Alert Is Visible
  ...  contains_message=Adviesaanvraag of opdrachtformulering '${file_name}' is toegevoegd.
  ...  selector=//*[@data-e2e-name="attachments"]//*[@data-e2e-name="alert"][contains(@class, 'bhr-alert--success')]

Upload And Submit DraftDecision Document
  [Arguments]  ${file_name}  ${document_location}
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${document_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${file_name}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Get Element Count  //dialog[@open]//*[@data-e2e-name="save-failed"]  should be  0

Upload And Submit DraftDecision Attachment
  [Arguments]  ${file_name}  ${attachment_location}  ${type}=adviesaanvraag
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${attachment_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${file_name}
  Select Options By  //dialog[@open]//select[@name="type"]  text  ${type}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Get Element Count  //dialog[@open]//*[@data-e2e-name="save-failed"]  should be  0

Get DraftDecision Attachment Types
  VAR  @{attachment_types} =
  ...  c_002fc258
  ...  c_03c52ba0
  ...  c_056a75e1
  ...  c_0670bae1
  ...  c_06a67c95
  ...  c_0d0e9b52
  ...  c_0e425c23
  ...  c_10eb451d
  ...  c_11305262
  ...  c_1422b182
  ...  c_1cf18f83
  ...  c_2977c34f
  ...  c_2ab17960
  ...  c_2aedadff
  ...  c_2c0438f4
  ...  c_30e8b503
  ...  c_38ba44de
  ...  c_3a121159
  ...  c_3d782f30
  ...  c_3eb5572a
  ...  c_41ac5574
  ...  c_42e406dd
  ...  c_45be34e9
  ...  c_49599e6c
  ...  c_4bd6c154
  ...  c_4efe1293
  ...  c_4f50ca9c
  ...  c_566a4430
  ...  c_5824891d
  ...  c_5b1055aa
  ...  c_61e3099a
  ...  c_622408c6
  ...  c_6604fe5b
  ...  c_6d494ab6
  ...  c_6f49bf34
  ...  c_7652d853
  ...  c_7eba29ad
  ...  c_8b92eab4
  ...  c_8f5898e5
  ...  c_9376c730
  ...  c_97f44ea5
  ...  c_99d3e284
  ...  c_9b2d26fc
  ...  c_9ecc0007
  ...  c_995d4fe9
  ...  c_a17ef403
  ...  c_a1dae55d
  ...  c_a40458df
  ...  c_a55b7649
  ...  c_a6f44748
  ...  c_acb44d77
  ...  c_aebfec50
  ...  c_b3a6a7fc
  ...  c_bee85371
  ...  c_bf0f9c95
  ...  c_c1956ef0
  ...  c_c2052ba5
  ...  c_c2f56984
  ...  c_cccba364
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
  ...  overig
  ...  ww_a2azo7q
  ...  ww_jc6woe9
  RETURN  ${attachment_types}
