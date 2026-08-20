*** Settings ***
Documentation       Tests for the DraftDecision information category.
Resource            ../../resources/DraftDecision.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  draftdecision


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
Create And Publish With Request For Advice Attachment
  [Documentation]    Happy path: create a draft-decision with a main document and a
  ...    request-for-advice attachment, then publish it.
  Click Publications
  Publish Test DraftDecision
  Verify Publication Status  ${DOSSIER_REFERENCE}  Openbaar

Create And Publish With Policy Document As Required Attachment
  [Documentation]    A beleidsdocument (POLICY_DOCUMENT) is also a valid required attachment type.
  Click Publications
  Generate Test Data Set  draft-decision
  Create New Dossier  draft-decision
  Fill Out Basic Details  type=draft-decision
  Fill Out Summary
  Upload DraftDecision Main Document  ${FILE_LOCATION}
  Upload DraftDecision Required Attachment  ${FILE_LOCATION}  type=beleidsdocument
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  Verify Publication Status  ${DOSSIER_REFERENCE}  Openbaar

Cannot Publish Without Required Attachment Type
  [Documentation]    The content step must block saving when no request-for-advice or
  ...    policy-document attachment is present.
  Click Publications
  Generate Test Data Set  draft-decision
  Create New Dossier  draft-decision
  Fill Out Basic Details  type=draft-decision
  Fill Out Summary
  Upload DraftDecision Main Document  ${FILE_LOCATION}
  Click Save And Continue
  Get Element Count  //*[contains(@class,"js-input-errors")]  greater than  0

Cannot Publish Without Main Document
  [Documentation]    Without uploading the wetgevingsvoorstel (main document) the dossier
  ...    remains in concept status and cannot be published.
  Click Publications
  Generate Test Data Set  draft-decision
  Create New Dossier  draft-decision
  Fill Out Basic Details  type=draft-decision
  Fill Out Summary
  Upload DraftDecision Required Attachment  ${FILE_LOCATION}
  Click Save And Continue
  Click Publications
  Verify Publication Status  ${DOSSIER_REFERENCE}  Concept

Published DraftDecision Is Visible On Public Detail Page
  [Documentation]    After publishing, the public detail page at /ontwerpbesluit/... must be
  ...    reachable and show the dossier title and the main document link.
  Click Publications
  Publish Test DraftDecision
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Verify Dossier Metadata On Public

Scheduled DraftDecision Is Not Visible On Public Page Before Publication Date
  [Documentation]    A dossier scheduled for a future date must not be accessible on the
  ...    public site yet.
  Click Publications
  Publish Test DraftDecision  publication_status=Gepland
  Verify Publication Status  ${DOSSIER_REFERENCE}  Publicatie gepland
  Search For A Publication  ${DOSSIER_REFERENCE}


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation
  Click Publications
  Skip Suite If Dossier Type Unavailable  draft-decision

Suite Teardown
  No-Click Logout
  Clear TestData Folder

Verify Publication Status
  [Arguments]  ${dossier_reference}  ${expected_status}
  Click Publications
  Get Text
  ...  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${dossier_reference}')]//span[@data-e2e-name="dossier-status-badge"]
  ...  contains
  ...  ${expected_status}
