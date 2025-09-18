*** Settings ***
Documentation       A collection of miscellaneous tests.
Resource            ../resources/Covenant.resource
Resource            ../resources/Departments.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  misc


*** Test Cases ***
Replace Main Document On A Public WooDecision
  Generate Test Data Set  woo-decision
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Decision
  Edit Main Document
  Upload Besluitbrief  files/dummy.txt

Replace Main Document On A Public Covenant
  Publish Test Covenant  has_attachment=${TRUE}
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  Edit Main Document
  Upload Covenant  files/dummy.txt

Edit An Existing Main Document
  [Documentation]  Relies on the previous test having created any kind of publication
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  Edit Main Document
  Fill Text  //dialog[@open]//input[@name="internalReference"]  1234567890
  Click  //dialog[@open]//button[@type="submit"]
  Success Alert Is Visible  is bijgewerkt

Remove An Attachment
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

Verify Document Relations
  [Tags]  relations
  Publish Test WooDecision
  ...  production_report=files/woodecision/relations.xlsx
  ...  documents=files/woodecision/relations.zip
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

Replace Main Document On A Preview WooDecision
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
  Upload Besluitbrief  files/dummy.txt

Update Department Texts
  Click Departments
  Click Specific Department  E2E-DEP1
  Fill Text  //*[@id="department_feedback_content"]  Stuur drie rooksignalen voor zonsondergang
  Fill Text  //*[@id="department_responsibility_content"]  Bassie is verantwoordelijk!
  Click Save Department
  Click Publications
  Publish Generated Test WooDecision
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Get Text  //*[@data-e2e-name="responsible"]  contains  Bassie is verantwoordelijk!
  Click  (//*[@data-e2e-name="tabs-documenten-content-1"]//tbody//a)[1]
  Get Text  //*[@data-e2e-name="grounds"]  contains  Stuur drie rooksignalen voor zonsondergang


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
  Clear TestData Folder
