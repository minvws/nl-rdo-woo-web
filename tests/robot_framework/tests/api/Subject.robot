*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Subjects API
Library             RequestsLibrary
Resource            ../../resources/API.resource
Suite Setup         Suite Setup API
Test Tags           api  subject


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${CREATED_SUBJECT}  ${EMPTY}
${UPDATED_SUBJECT}  ${EMPTY}
${NEW_NAME}         ${EMPTY}
${IN_USE_SUBJECT}   ${EMPTY}


*** Test Cases ***
Get All Subjects
  [Documentation]  Reads all subjects and checks if the E2E Test Subject, from the fixtures, is present.
  [Tags]  api-single
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  msg=GET request failed
  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Subject
  Status Should Be  200  ${response}  msg=GET request did not return a 200

Create A Subject
  When A Subject Is Created
  Then We Can Find It

Update A Subject
  When The Subject Is Updated
  Then The Subject Has The New Values

Delete An Unused Subject
  When The Subject Is Deleted
  Then We Cannot Find It

Delete A Used Subject
  When A Subject Is Created And Linked To A Dossier
  Then We Cannot Delete It


*** Keywords ***
A Subject Is Created
  ${id} =  FakerLibrary.Md 5
  VAR  ${name} =  Random Subject - ${id}
  VAR  &{subject} =  name=${name}
  ${post_response} =  POST On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  json=${subject}
  ...  expected_status=201
  ...  msg=POST request failed
  VAR  ${CREATED_SUBJECT} =  ${post_response.json()}  scope=suite

We Can Find It
  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  expected_status=200

The Subject Is Updated
  ${id_updated} =  FakerLibrary.Md 5
  VAR  ${NEW_NAME} =  Random Subject ${id_updated}  scope=suite
  VAR  &{body} =  name=${NEW_NAME}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  json=${body}
  ...  expected_status=200
  ...  msg=PUT request failed
  VAR  ${UPDATED_SUBJECT} =  ${put_response.json()}  scope=suite
  Should Be Equal As Strings  ${put_response.json()}[name]  ${NEW_NAME}

The Subject Has The New Values
  Should Be Equal As Strings  ${UPDATED_SUBJECT}[name]  ${NEW_NAME}

The Subject Is Deleted
  DELETE On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  expected_status=204
  ...  msg=DELETE request failed

We Cannot Find It
  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  expected_status=404

A Subject Is Created And Linked To A Dossier
  ${id} =  FakerLibrary.Word
  VAR  ${name} =  Random Subject - ${id}
  VAR  &{subject} =  name=${name}
  ${post_response} =  POST On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  json=${subject}
  ...  expected_status=201
  ...  msg=POST subject failed
  VAR  ${IN_USE_SUBJECT} =  ${post_response.json()}  scope=suite
  ${dossier_ext_id} =  FakerLibrary.Md 5
  ${today} =  Get Current Date  result_format=%Y-%m-%d
  ${grounds} =  Get Random Grounds
  VAR  &{main_doc} =
  ...  fileName=dummy.txt
  ...  formalDate=${today}
  ...  grounds=${grounds}
  ...  language=NLD
  ...  type=c_3d782f30
  VAR  @{attachments} =
  ${department_id} =  Get Department ID
  VAR  &{body} =
  ...  departmentId=${department_id}
  ...  dossierNumber=robot-api-${dossier_ext_id}
  ...  publicationDate=${today}
  ...  subjectId=${IN_USE_SUBJECT}[id]
  ...  summary=Robot subject in use test
  ...  title=Robot Subject In Use ${dossier_ext_id}
  ...  year=${2025}
  ...  mainDocument=${main_doc}
  ...  attachments=${attachments}
  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/external/${dossier_ext_id}
  ...  json=${body}
  ...  expected_status=200
  ...  msg=Create annual-report dossier failed

We Cannot Delete It
  DELETE On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${IN_USE_SUBJECT}[id]
  ...  expected_status=405
  ...  msg=DELETE of used subject should return 405
