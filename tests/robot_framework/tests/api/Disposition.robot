*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Disposition endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  disposition


*** Variables ***
${ORGANISATION_ID}      ${EMPTY}
${CURRENT_DATE_RFC}     ${EMPTY}
${EXTERNAL_ID}          ${EMPTY}
${BODY}                 ${EMPTY}


*** Test Cases ***
Create A Disposition Without Uploading Files
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Disposition  ${publication_date}
  And A Disposition Is Created
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Disposition
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_2ab17960
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${title} =  Catenate  Robot API ${dossier_number}
  ${summary} =  Fakerlibrary.Text  200
  ${dossier_date} =  Get Date Minus  1 day
  VAR  &{BODY} =
  ...  attachments=@{EMPTY}
  ...  departmentId=${department_id}
  ...  dossierDate=${dossier_date}
  ...  dossierNumber=${dossier_number}
  ...  internalReference=
  ...  mainDocument=${main_document}
  ...  prefix=${prefix_id}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

A Disposition Is Created
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/disposition/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/disposition/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST

A Disposition Is Updated
  Set To Dictionary  ${BODY}  title  Robot - Gewijzigde titel
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/disposition/${EXTERNAL_ID}
  ...  json=${BODY}

The Disposition Has The New Values
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/disposition/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[title]  Robot - Gewijzigde titel
