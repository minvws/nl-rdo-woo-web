*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Request For Advice endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Resource            ../../resources/RequestForAdvice.resource
Suite Setup         Suite Setup
Test Tags           api  request-for-advice


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}


*** Test Cases ***
Create And Publish An Request For Advice
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Request For Advice  ${publication_date}
  And We Create An Request For Advice
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Request For Advice
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_a40458df
  ${allowed_attachment_types} =  Get Request For Advice Attachment Types
  ${attachments} =  Generate Attachments  ${allowed_attachment_types}
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  VAR  ${dossier_date} =  ${CURRENT_DATE}
  ${dossier_number} =  Generate Dossier Reference Number
  ${summary} =  Fakerlibrary.Text  200
  ${title} =  Catenate  Robot API ${dossier_number}
  VAR  ${link} =  http://www.rijksoverheid.nl
  VAR  @{advisory_bodies} =  []
  VAR  &{BODY} =
  ...  advisoryBodies=${advisory_bodies}
  ...  attachments=${attachments}
  ...  departmentId=${department_id}
  ...  dossierDate=${dossier_date}
  ...  dossierNumber=${dossier_number}
  ...  link=${link}
  ...  mainDocument=${main_document}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

We Create An Request For Advice
  PUT On Session
  ...  alias=publication_api
  ...  url=%{URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/request-for-advice/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=%{URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/request-for-advice/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST
