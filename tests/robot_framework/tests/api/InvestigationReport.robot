*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Investigation Report endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Resource            ../../resources/InvestigationReport.resource
Suite Setup         Suite Setup
Test Tags           api  investigation-report


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}


*** Test Cases ***
Create And Publish An Investigation Report
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Investigation Report  ${publication_date}
  And We Create An Investigation Report
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Investigation Report
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_38ba44de
  ${allowed_attachment_types} =  Get Investigation Report Attachment Types
  ${attachments} =  Generate Attachments  ${allowed_attachment_types}
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${dossier_number} =  Generate Dossier Reference Number
  ${title} =  Catenate  Robot API ${dossier_number}
  ${summary} =  Fakerlibrary.Text  200
  VAR  ${dossier_date} =  ${CURRENT_DATE}
  VAR  &{BODY} =
  ...  attachments=${attachments}
  ...  departmentId=${department_id}
  ...  dossierDate=${dossier_date}
  ...  dossierNumber=${dossier_number}
  ...  mainDocument=${main_document}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

We Create An Investigation Report
  PUT On Session
  ...  alias=publication_api
  ...  url=%{URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/investigation-report/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=%{URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/investigation-report/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST
