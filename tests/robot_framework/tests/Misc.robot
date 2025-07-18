*** Settings ***
Documentation       A collection of miscellaneous tests.
Resource            ../resources/Covenant.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
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

Verify document relations
  [Tags]  relations
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/relations.xlsx
  ...  documents=tests/robot_framework/files/woodecision/relations.zip
  ...  number_of_documents=10
  ...  prefix=E2E-A
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  # Check decision of 10
  Get Element Count  //*[@data-e2e-name="documents-section"]//tbody//tr  should be  10
  Click  //*[@data-e2e-name="documents-section"]//tbody//tr//a[contains(.,"doc1.pdf")]
  # Check family id
  Get Element Count  //*[@data-e2e-name="documents-section"]//tbody//tr  should be  5
  Click  //*[@data-e2e-name="documents-section"]//tbody//tr//a[contains(.,"email2")]
  # Check related id
  Get Text  //*[@data-e2e-name="notifications"]  contains  test doc5.pdf heeft een sterke relatie met dit document.
  # Check email thread id
  Get Element Count  //*[@data-e2e-name="documents-section"][1]//tbody//tr  should be  3
  # Check email attachments
  Get Element Count  //*[@data-e2e-name="documents-section"][2]//tbody//tr  should be  5

Replace main document on a preview WooDecision
  Generate Test Data Set  woo-decision
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  ...  publication_status=Gepland
  Search For A Publication  ${DOSSIER_REFERENCE}
  # Now we open the publication, edit the decision and upload a new main document (besluitbrief)
  Click Edit Decision
  Edit Main Document
  Upload Besluitbrief  tests/robot_framework/files/dummy.pdf


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
  Clear TestData Folder
