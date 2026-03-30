*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Advice endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Advice.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Tags           api  advice


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}
${TODAY}            ${EMPTY}


*** Test Cases ***
Create And Publish An Advice
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Advice  ${publication_date}
  And We Create An Advice
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Advice
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_d506b718
  ${allowed_attachment_types} =  Get Advice Attachment Types
  ${attachments} =  Generate Attachments  ${allowed_attachment_types}
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${summary} =  Fakerlibrary.Text  200
  ${title} =  Catenate  Robot API ${dossier_number}
  VAR  ${dossier_date} =  ${TODAY}
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

We Create An Advice
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/advice/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/advice/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST
