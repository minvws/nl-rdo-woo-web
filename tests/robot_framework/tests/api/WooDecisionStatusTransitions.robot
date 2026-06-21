*** Comments ***
# robotcode: ignore


*** Settings ***
Documentation       Status transition tests for WooDecision dossiers.
...                 These tests verify that backward status transitions triggered by date changes
...                 correctly remove the dossier and its documents from public access.
...
...                 Allowed transitions under test:
...                 PUBLISHED > PREVIEW, PUBLISHED > SCHEDULED, PUBLISHED > CONCEPT
...                 PREVIEW > SCHEDULED, PREVIEW > CONCEPT
...                 SCHEDULED > CONCEPT
...
...                 All tests are tagged [failing] until the backward transition feature is implemented.
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup API
Test Tags           api  api-woodecision-transitions


*** Variables ***
${EXTERNAL_ID}              ${EMPTY}
${PREVIOUS_REQUEST_BODY}    ${EMPTY}
${DOSSIER_PUBLIC_URL}       ${EMPTY}


*** Test Cases ***
Transition From Published To Preview
  [Tags]  failing
  Create WooDecision Dossier In Status  published
  Set Dossier Dates  preview
  Update WooDecision Dates  preview
  Verify WooDecision Documents Are Not Publicly Accessible

Transition From Published To Scheduled
  [Tags]  failing
  Create WooDecision Dossier In Status  published
  Store WooDecision Public URL
  Set Dossier Dates  scheduled
  Update WooDecision Dates  scheduled
  Verify WooDecision Is Not Publicly Accessible
  Verify WooDecision Documents Are Not Publicly Accessible

Transition From Published To Concept
  [Tags]  failing
  Create WooDecision Dossier In Status  published
  Store WooDecision Public URL
  Set Dossier Dates  concept
  Update WooDecision Dates  concept
  Verify WooDecision Is Not Publicly Accessible
  Verify WooDecision Documents Are Not Publicly Accessible

Transition From Preview To Scheduled
  [Tags]  failing
  Create WooDecision Dossier In Status  preview
  Store WooDecision Public URL
  Set Dossier Dates  scheduled
  Update WooDecision Dates  scheduled
  Verify WooDecision Is Not Publicly Accessible
  Verify WooDecision Documents Are Not Publicly Accessible

Transition From Preview To Concept
  [Tags]  failing
  Create WooDecision Dossier In Status  preview
  Store WooDecision Public URL
  Set Dossier Dates  concept
  Update WooDecision Dates  concept
  Verify WooDecision Is Not Publicly Accessible
  Verify WooDecision Documents Are Not Publicly Accessible

Transition From Scheduled To Concept
  [Tags]  failing
  Create WooDecision Dossier In Status  scheduled
  Set Dossier Dates  concept
  Update WooDecision Dates  concept


*** Keywords ***
Create WooDecision Dossier In Status
  [Arguments]  ${target_status}
  Generate External ID
  ${body} =  Build Request Body For WooDecision  ${target_status}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Upload Main Document  woo-decision  ${TEST_DATA_ROOT}/dummy.txt  ${EXTERNAL_ID}
  Upload Document  woo-decision  ${TEST_DATA_ROOT}/dummy.txt  ${EXTERNAL_ID}  ${body}[documents][0][externalId]
  Wait Until Keyword Succeeds  5x  2s  Publication Status Should Be  woo-decision  ${target_status}

Get Dates For Status
  [Arguments]  ${status}
  IF  '${status}' == 'published'
    ${preview_date} =  Get Date Minus  1 day
    ${publication_date} =  Get Date Minus  1 day
  ELSE IF  '${status}' == 'preview'
    ${preview_date} =  Get Date Plus  0 days
    ${publication_date} =  Get Date Plus  7 days
  ELSE IF  '${status}' == 'scheduled'
    ${preview_date} =  Get Date Plus  7 days
    ${publication_date} =  Get Date Plus  14 days
  ELSE IF  '${status}' == 'concept'
    ${preview_date} =  Get Date Plus  3650 days
    ${publication_date} =  Get Date Plus  3650 days
  ELSE
    Fail  Unknown target status: ${status}
  END
  RETURN  ${preview_date}  ${publication_date}

Set Dossier Dates
  [Arguments]  ${target_status}
  ${preview_date}  ${publication_date} =  Get Dates For Status  ${target_status}
  Set To Dictionary  ${PREVIOUS_REQUEST_BODY}  previewDate  ${preview_date}
  Set To Dictionary  ${PREVIOUS_REQUEST_BODY}  publicationDate  ${publication_date}

Update WooDecision Dates
  [Arguments]  ${expected_status}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${PREVIOUS_REQUEST_BODY}  200
  Wait Until Keyword Succeeds  5x  2s  Publication Status Should Be  woo-decision  ${expected_status}

Store WooDecision Public URL
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  VAR  ${DOSSIER_PUBLIC_URL} =  ${get_response.json()}[_links][public][href]  scope=test

Verify WooDecision Is Not Publicly Accessible
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  Dictionary Should Not Contain Key  ${get_response.json()}[_links]  public
  ...  msg=Expected dossier _links.public to be absent (status=${get_response.json()}[status])
  ${public_url} =  Get Variable Value  ${DOSSIER_PUBLIC_URL}  ${EMPTY}
  IF  '${public_url}' != '${EMPTY}'
    Create Public Session
    ${path} =  Remove String Using Regexp  ${public_url}  ^https?://[^/]+
    ${public_response} =  GET On Session  alias=public  url=${path}  expected_status=any
    Should Be Equal As Integers
    ...  ${public_response.status_code}
    ...  404
    ...  msg=Expected public dossier page ${public_url} to return 404 but got ${public_response.status_code}
  END

Verify WooDecision Documents Are Not Publicly Accessible
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  FOR  ${document}  IN  @{get_response.json()}[documents]
    Dictionary Should Not Contain Key
    ...  ${document}[_links]
    ...  public
    ...  msg=Expected document _links.public to be absent (documentNr=${document}[documentNr], status=${get_response.json()}[status])
  END

Build Request Body For WooDecision
  [Arguments]  ${target_status}
  ${dossier_reference} =  Generate Dossier Reference Number
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${date_from} =  Get Date Minus  14 days
  ${date_to} =  Get Date Minus  7 days
  ${main_document} =  Generate Main Document  type=c_4f50ca9c
  VAR  @{attachments} =  @{EMPTY}
  ${document_id} =  FakerLibrary.Random Int  min=100000  max=999999
  ${document_id} =  Convert To String  ${document_id}
  ${document_external_id} =  FakerLibrary.Uuid 4
  ${grounds} =  Get Random Grounds
  VAR  &{document} =
  ...  externalId=${document_external_id}
  ...  inquiryNumbers=${{ ['E2E-CASE-001'] }}
  ...  documentDate=2020-06-01
  ...  documentId=${document_id}
  ...  familyId=${333}
  ...  fileName=quality.doc
  ...  grounds=${grounds}
  ...  isSuspended=${FALSE}
  ...  judgement=public
  ...  links=${{ [] }}
  ...  matter=2025-01
  ...  refersTo=${{ [] }}
  ...  remark=Niets op aan te merken
  ...  sourceType=doc
  ...  threadId=${12345}
  VAR  @{documents} =  ${document}
  ${preview_date}  ${publication_date} =  Get Dates For Status  ${target_status}
  VAR  &{body} =
  ...  decision=public
  ...  departmentId=${department_id}
  ...  dateFrom=${date_from}
  ...  dateTo=${date_to}
  ...  dossierNumber=robot-api-${dossier_reference}
  ...  previewDate=${preview_date}
  ...  publicationDate=${publication_date}
  ...  reason=woo_request
  ...  subjectId=${subject_id}
  ...  summary=E2E status transition test dossier
  ...  title=Robot API ${dossier_reference}
  ...  mainDocument=${main_document}
  ...  attachments=${attachments}
  ...  documents=${documents}
  VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test
  RETURN  ${body}
