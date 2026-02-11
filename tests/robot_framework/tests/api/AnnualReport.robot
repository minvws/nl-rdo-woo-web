*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Annual Report endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  annual-report


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}
${RESPONSE}         ${EMPTY}


*** Test Cases ***
Create And Publish An Annual Report
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Annual Report  ${publication_date}
  And We Create An Annual Report
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Annual Report
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_38ba44de
  ${attachments} =  Generate Attachments
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${title} =  Catenate  Robot API ${dossier_number}
  ${summary} =  Fakerlibrary.Text  200
  ${year} =  Convert To Integer  2025
  VAR  &{BODY} =
  ...  attachments=${attachments}
  ...  departmentId=${department_id}
  ...  dossierNumber=${dossier_number}
  ...  internalReference=${internal_reference}
  ...  mainDocument=${main_document}
  ...  prefix=${prefix_id}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  year=${year}
  ...  scope=SUITE
  Generate External ID

We Create An Annual Report
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST

A Annual Report Is Updated
  Set To Dictionary  ${BODY}  title  Robot - Gewijzigde titel
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/${EXTERNAL_ID}
  ...  json=${BODY}

The Annual Report Has The New Values
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[title]  Robot - Gewijzigde titel
