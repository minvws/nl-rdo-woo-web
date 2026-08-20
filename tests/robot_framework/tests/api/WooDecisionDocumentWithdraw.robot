*** Comments ***
# robotcode: ignore


*** Settings ***
Documentation       API tests for withdrawing a document of a WooDecision dossier.
Resource            ../../resources/WooDecisionAPI.resource
Suite Setup         Suite Setup API
Test Tags           api  api-woodecision-withdraw


*** Variables ***
${EXTERNAL_ID}              ${EMPTY}
${PREVIOUS_REQUEST_BODY}    ${EMPTY}


*** Test Cases ***
Withdraw Document Successfully
  ${document_external_id} =  Create Published WooDecision Document
  VAR  &{body} =  reason=data_in_document  explanation=Contains unredacted personal data on page 3.
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  202
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document Should Be Withdrawn  ${EXTERNAL_ID}  ${document_external_id}
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document File Should Not Be Downloadable  ${EXTERNAL_ID}  ${document_external_id}
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document Detail Page Should Show Withdrawn Notice  ${EXTERNAL_ID}  ${document_external_id}

Withdraw Document Without Reason Returns 422
  ${document_external_id} =  Create Published WooDecision Document
  VAR  &{body} =  explanation=Contains unredacted personal data on page 3.
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  422

Withdraw Document Without Explanation Returns 422
  ${document_external_id} =  Create Published WooDecision Document
  VAR  &{body} =  reason=data_in_document
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  422

Withdraw Document With Invalid Reason Returns 422
  ${document_external_id} =  Create Published WooDecision Document
  VAR  &{body} =  reason=invalid  explanation=Contains unredacted personal data on page 3.
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  422

Withdraw Already Withdrawn Document Returns 422
  ${document_external_id} =  Create Published WooDecision Document
  VAR  &{body} =  reason=data_in_document  explanation=Contains unredacted personal data on page 3.
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  202
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document Should Be Withdrawn  ${EXTERNAL_ID}  ${document_external_id}
  Withdraw WooDecision Document  ${EXTERNAL_ID}  ${document_external_id}  ${body}  422


*** Keywords ***
Create Published WooDecision Document
  [Documentation]  Creates a published WooDecision dossier with one document and returns its externalId.
  Create WooDecision Dossier In Status  published
  RETURN  ${PREVIOUS_REQUEST_BODY}[documents][0][externalId]
