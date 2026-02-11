*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Other Publication endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  other-publication


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}


*** Test Cases ***
Create A Other Publication
  When A Other Publication Is Created
  Then We Can Find It


*** Keywords ***
Suite Setup
  Suite Setup API
  Create Test Data For Other Publication

Create Test Data For Other Publication
  ${main_document} =  Generate Main Document  type=overig
  ${attachments} =  Generate Attachments
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${title} =  Catenate  Robot API ${dossier_number}
  ${summary} =  Fakerlibrary.Text  200
  ${dossier_date} =  Get Date Minus  1 day
  ${publication_date} =  Get Date Plus  1 week
  VAR  &{BODY} =
  ...  attachments=${attachments}
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

A Other Publication Is Created
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/other-publication/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/other-publication/${EXTERNAL_ID}

A Other Publication Is Updated
  Set To Dictionary  ${BODY}  title  Robot - Gewijzigde titel
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/other-publication/${EXTERNAL_ID}
  ...  json=${BODY}

The Other Publication Has The New Values
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/other-publication/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[title]  Robot - Gewijzigde titel
