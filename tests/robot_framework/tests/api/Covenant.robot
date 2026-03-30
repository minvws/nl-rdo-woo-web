*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Covenant endpoint
Library             RequestsLibrary
Resource            ../../resources/API.resource
Resource            ../../resources/Covenant.resource
Suite Setup         Suite Setup
Test Tags           api  covenant


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${BODY}             ${EMPTY}


*** Test Cases ***
Create And Publish An Covenant
  ${publication_date} =  Get Date Minus  1 day
  When We Create Test Data For Covenant  ${publication_date}
  And We Create An Covenant
  And We Dont Upload The Main Document
  Then We Can Find It
  And Its Not Published Yet


*** Keywords ***
Suite Setup
  Suite Setup API

We Create Test Data For Covenant
  [Arguments]  ${publication_date}
  ${main_document} =  Generate Main Document  type=c_38ba44de
  ${allowed_attachment_types} =  Get Covenant Attachment Types
  ${attachments} =  Generate Attachments  ${allowed_attachment_types}
  # Build body
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  ${prefix_id} =  Get Prefix
  ${dossier_number} =  Generate Dossier Reference Number
  ${internal_reference} =  FakerLibrary.Sentence
  ${summary} =  Fakerlibrary.Text  200
  ${title} =  Catenate  Robot API ${dossier_number}
  ${date_from} =  Get Date Minus  7 day
  ${date_to} =  Get Date Minus  2 day
  VAR  ${previous_version_link} =  ${EMPTY}
  VAR  @{parties} =  Party 1  Party 2
  VAR  &{BODY} =
  ...  attachments=${attachments}
  ...  dateFrom=${date_from}
  ...  dateTo=${date_to}
  ...  departmentId=${department_id}
  ...  dossierNumber=${dossier_number}
  ...  internalReference=${internal_reference}
  ...  mainDocument=${main_document}
  ...  parties=${parties}
  ...  prefix=${prefix_id}
  ...  previousVersionLink=${previous_version_link}
  ...  publicationDate=${publication_date}
  ...  subjectId=${subject_id}
  ...  summary=${summary}
  ...  title=${title}
  ...  scope=SUITE
  Generate External ID

We Create An Covenant
  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/covenant/${EXTERNAL_ID}
  ...  json=${BODY}

We Can Find It
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/covenant/${EXTERNAL_ID}
  VAR  ${RESPONSE} =  ${get_response.json()}  scope=TEST
