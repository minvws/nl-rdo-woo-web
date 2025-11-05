*** Settings ***
Documentation       A collection of miscellaneous tests.
Resource            ../../resources/Covenant.resource
Resource            ../../resources/Departments.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  misc


*** Test Cases ***
Replace Main Document On A Public WooDecision
  Publish Generated Test WooDecision
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
  Click Breadcrumb Element  3
  Get Text  id=inhoud  not contains  ${attachment_file_name}

Verify Document Relations
  [Tags]  relations
  ${new_prefix} =  Add A Random Organisation Prefix
  Click Publications
  Publish Test WooDecision
  ...  production_report=files/woodecision/relations.xlsx
  ...  documents=files/woodecision/relations.zip
  ...  number_of_documents=10
  ...  prefix=${new_prefix}
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
  Publish Generated Test WooDecision  publication_status=Gepland
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

Create A WooDecision
  [Documentation]  Simple and quick testcase to create a WooDecision
  [Tags]  -ci  single
  Publish Generated Test WooDecision

Upload A File With Incorrect File And/Or Mimetype
  [Documentation]  Uploads a PDF hidden as an Excel file to verify that this is not allowed.
  VAR  ${type} =  covenant
  Generate Test Data Set  ${type}
  Create New Dossier  ${type}
  Fill Out Basic Details  type=${type}
  Click  " Convenant toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  files/magicbytes-fail.xlsx
  Get Text
  ...  //dialog[@open]//p
  ...  contains
  ...  Er zijn mogelijk gevaren gevonden in het bestand, het bestand wordt niet opgeslagen. Probeer een ander bestand.


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
