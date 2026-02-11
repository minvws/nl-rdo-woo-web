*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Complaint Judgement endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  complaint-judgement


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}


*** Test Cases ***
Create A Complaint Judgement
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Complaint Judgement  ${publication_date}
  And A Complaint Judgement Is Created
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Complaint Judgement
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=ww_jc6woe9
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
  ...  departmentId=${department_id}
  ...  dossierDate=${dossier_date}
  ...  dossierNumber=${dossier_number}
  ...  internalReference=${internal_reference}
  ...  mainDocument=${main_document}
  ...  prefix=${prefix_id}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

A Complaint Judgement Is Created
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/complaint-judgement/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/complaint-judgement/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST

A Complaint Judgement Is Updated
  Set To Dictionary  ${BODY}  title  Robot - Gewijzigde titel
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/complaint-judgement/${EXTERNAL_ID}
  ...  json=${BODY}

The Complaint Judgement Has The New Values
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/complaint-judgement/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[title]  Robot - Gewijzigde titel
