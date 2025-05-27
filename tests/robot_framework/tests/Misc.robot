*** Settings ***
Documentation       A collection of miscellaneous tests.
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Covenant.resource
Resource            ../resources/Organisations.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  misc


*** Test Cases ***
Replace main document on a public WooDecision
  Generate Test Data Set  woo-decision
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Decision
  Edit Main Document
  Upload Besluitbrief  tests/robot_framework/files/dummy.pdf

Replace main document on a public Covenant
  Publish Test Covenant  has_attachment=${TRUE}
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  Edit Main Document
  Upload Covenant  tests/robot_framework/files/dummy.pdf

Edit an existing main document
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  Edit Main Document
  Fill Text  //dialog[@open]//input[@name="internalReference"]  1234567890
  Click  //dialog[@open]//button[@type="submit"]
  # Implement 'Success Alert Is Visible' here, #4566
  Wait For Condition  Text  //*[@id="inhoud"]  contains  is bijgewerkt

Remove an attachment
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  ${attachment_file_name} =  Get Text
  ...  //*[@data-e2e-name="attachments"]//span[contains(@class,'bhr-file__file-name')]
  Click  //*[@data-e2e-name="attachments"]//*[@data-e2e-name="withdraw-file"]
  Check Checkbox  id=withdraw_attachment_form_reason_0
  ${reason} =  Fakerlibrary.Text  200
  Fill Text  id=withdraw_attachment_form_explanation  ${reason}
  Click  id=withdraw_attachment_form_submit
  Success Alert Is Visible  De bijlage is ingetrokken.
  Click Breadcrumb Element  2
  Get Text  id=inhoud  not contains  ${attachment_file_name}


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
  Clear TestData Folder
