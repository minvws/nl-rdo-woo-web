*** Settings ***
Library     FakerLibrary
Resource    API.resource
Resource    Dossier.resource


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
  ...  publicationContext=2025-01
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
  VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test  # robotcode: ignore
  RETURN  ${body}

Withdraw WooDecision Document
  [Arguments]  ${dossier_external_id}  ${document_external_id}  ${body}  ${expected_response_status}
  ${path} =  Catenate  SEPARATOR=${EMPTY}
  ...  /api/publication/v1/organisation/${ORGANISATION_ID}
  ...  /dossiers/woo-decision/external/${dossier_external_id}
  ...  /uploads/document/external/${document_external_id}/withdraw
  ${response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}${path}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${response.status_code} == ${expected_response_status}
  ...  msg=Withdraw document PUT returned ${response.status_code} while expecting ${expected_response_status}
  RETURN  ${response}

Get WooDecision Document By External Id
  [Arguments]  ${dossier_external_id}  ${document_external_id}
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${dossier_external_id}
  FOR  ${document}  IN  @{get_response.json()}[documents]
    IF  '${document}[externalId]' == '${document_external_id}'  RETURN  ${document}
  END
  Fatal Error  No document found with externalId '${document_external_id}'

WooDecision Document Should Be Withdrawn
  [Arguments]  ${dossier_external_id}  ${document_external_id}
  ${document} =  Get WooDecision Document By External Id  ${dossier_external_id}  ${document_external_id}
  Should Be True  ${document}[isWithdrawn]  msg=Expected document ${document_external_id} to be withdrawn

WooDecision Document File Should Not Be Downloadable
  [Arguments]  ${dossier_external_id}  ${document_external_id}
  ${document} =  Get WooDecision Document By External Id  ${dossier_external_id}  ${document_external_id}
  Create Public Session
  ${path} =  Remove String Using Regexp  ${document}[_links][file][href]  ^https?://[^/]+
  ${response} =  GET On Session  alias=public  url=${path}  expected_status=any
  Should Be Equal As Integers
  ...  ${response.status_code}
  ...  404
  ...  msg=Expected withdrawn document file ${path} to return 404 but got ${response.status_code}

WooDecision Document Detail Page Should Show Withdrawn Notice
  [Arguments]  ${dossier_external_id}  ${document_external_id}
  ${document} =  Get WooDecision Document By External Id  ${dossier_external_id}  ${document_external_id}
  ${dossier_path} =  Remove String Using Regexp  ${document}[_links][public][href]  ^https?://[^/]+
  ${path} =  Catenate  SEPARATOR=${EMPTY}  ${dossier_path}  /document/  ${document}[documentNumber]
  Create Public Session
  ${response} =  GET On Session  alias=public  url=${path}  expected_status=any
  Should Be Equal As Integers
  ...  ${response.status_code}
  ...  200
  ...  msg=Expected document detail page ${path} to return 200 but got ${response.status_code}
  Should Contain
  ...  ${response.text}
  ...  Dit document is op dit moment niet beschikbaar
  ...  msg=Expected document detail page ${path} to show the withdrawn notice
