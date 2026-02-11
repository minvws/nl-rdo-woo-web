*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the WooDecision endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  api-woodecision


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}
${RESPONSE}         ${EMPTY}


*** Test Cases ***
Without Main Document
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For WooDecision  ${publication_date}
  And A WooDecision Is Created
  Then We Can Find It
  And Its Not Published Yet

Main Document Is A Text File
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For WooDecision  ${publication_date}  ${TEST_DATA_ROOT}/dummy.txt
  And A WooDecision Is Created
  And We Upload The Main Document  ${TEST_DATA_ROOT}/dummy.txt
  Then We Can Find It
  And Its Not Published Yet

Main Document Is A PDF File
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For WooDecision  ${publication_date}  ${TEST_DATA_ROOT}/filetypes/16115.pdf
  And A WooDecision Is Created
  And We Upload The Main Document  ${TEST_DATA_ROOT}/filetypes/16115.pdf
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For WooDecision
  [Arguments]  ${publication_date}  ${main_document_location}=${TEST_DATA_ROOT}/dummy.txt
  ${main_document} =  Generate Main Document  ${main_document_location}
  ${documents} =  Generate WooDocuments
  ${attachments} =  Generate Attachments
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${title} =  Catenate  Robot API ${dossier_number}
  ${summary} =  Fakerlibrary.Text  200
  VAR  ${decision} =  public
  VAR  ${reason} =  woo_request
  ${date_from} =  Get Date Minus  2 weeks
  ${date_to} =  Get Date Minus  1 week
  ${preview_date} =  Get Date Minus  1 day
  VAR  &{BODY} =
  ...  attachments=${attachments}
  ...  decision=${decision}
  ...  departmentId=${department_id}
  ...  documents=${documents}
  ...  dossierDateFrom=${date_from}
  ...  dossierDateTo=${date_to}
  ...  dossierNumber=${dossier_number}
  ...  internalReference=${internal_reference}
  ...  mainDocument=${main_document}
  ...  prefix=${prefix_id}
  ...  previewDate=${preview_date}
  ...  publicationDate=${publication_date}
  ...  reason=${reason}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

Generate WooDocuments
  VAR  @{documents} =  @{EMPTY}
  VAR  @{refers_to} =  @{EMPTY}
  WHILE  True  limit=5  on_limit=pass
    ${document} =  Generate WooDocument  ${refers_to}
    Append To List  ${documents}  ${document}
    Append To List  ${refers_to}  ${document}[externalId]
  END
  RETURN  ${documents}

Generate WooDocument
  [Arguments]  ${refers_to}=@{EMPTY}
  ${document_id} =  FakerLibrary.Uuid 4
  ${external_id} =  FakerLibrary.Uuid 4
  ${document_nr} =  Generate Random Filename
  ${date} =  Convert Date To RFC 3339  2020-06-24
  ${file_name} =  File Name  extension=doc
  VAR  ${judgement} =  public
  VAR  ${remark} =  Niets op aan te merken
  VAR  ${matter} =  2025-01
  VAR  ${source_type} =  doc
  ${family_id} =  Convert To Integer  333
  ${thread_id} =  Convert To Integer  12345
  VAR  @{grounds} =  pietjepuk
  # Create dictionary
  VAR  &{document} =
  ...  externalId=${external_id}
  ...  caseNumbers=@{EMPTY}
  ...  date=${date}
  ...  documentId=${document_id}
  ...  documentNr=${document_nr}
  ...  familyId=${family_id}
  ...  fileName=${file_name}
  ...  grounds=${grounds}
  ...  isSuspended=${False}
  ...  judgement=${judgement}
  ...  links=@{EMPTY}
  ...  matter=${matter}
  ...  period=
  ...  refersTo=@{refers_to}
  ...  remark=${remark}
  ...  sourceType=${source_type}
  ...  threadId=${thread_id}
  RETURN  ${document}

A WooDecision Is Created
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/${EXTERNAL_ID}
  ...  json=${BODY}
  VAR  ${RESPONSE} =  ${put_response.json()}  scope=suite

We Can Find It
  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/${EXTERNAL_ID}

We Upload The Main Document
  [Arguments]  ${file_location}
  VAR  ${dossier_id} =  ${RESPONSE}[id]
  VAR  ${main_document_id} =  ${RESPONSE}[mainDocument][id]
  Upload File  woo-decision  ${dossier_id}  ${main_document_id}  ${file_location}  main-document
