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


*** Test Cases ***
Get All Subjects
  [Documentation]  Reads all subjects and checks if the E2E Test Subject, from the fixtures, is present.
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  msg=GET request failed
  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Subject
  Status Should Be  200  ${response}  msg=GET request did not return a 200

Create A Subject
  When A Subject Is Created
  Then We Can Find It

Update A Subject
  When The Subject Is Updated
  Then The Subject Has The New Values


*** Keywords ***
Suite Setup
  Create Session
  Retrieve Organisation ID

A Subject Is Created
  ${id} =  FakerLibrary.Md 5
  VAR  ${name} =  Random Subject ${id}
  VAR  &{subject} =  name=${name}
  ${post_response} =  POST On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  json=${subject}
  ...  expected_status=201
  ...  msg=POST request failed
  VAR  ${CREATED_SUBJECT} =  ${post_response.json()}  scope=suite

We Can Find It
  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  expected_status=200

The Subject Is Updated
  ${id_updated} =  FakerLibrary.Md 5
  VAR  ${NEW_NAME} =  Random Subject ${id_updated}  scope=suite
  VAR  &{body} =  name=${NEW_NAME}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${CREATED_SUBJECT}[id]
  ...  json=${body}
  ...  expected_status=200
  ...  msg=PUT request failed
  VAR  ${UPDATED_SUBJECT} =  ${put_response.json()}  scope=suite
  Should Be Equal As Strings  ${put_response.json()}[name]  ${NEW_NAME}

The Subject Has The New Values
  Should Be Equal As Strings  ${UPDATED_SUBJECT}[name]  ${NEW_NAME}
