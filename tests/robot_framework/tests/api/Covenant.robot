*** Settings ***
Documentation       Tests for the Covenant endpoint, utilizing a custom DataDriver reader. Actual testcases are in the file files/api/covenant.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/covenant.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       Covenant Test Case
Test Tags           api  api-covenant


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

Covenant Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    IF  '${step}[type]' == 'request'
      Create Covenant
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create Covenant
  [Arguments]
  ...  ${expected_response_status}
  ...  ${body}
  ...  ${files}
  ...  ${expected_publication_status}
  ...  ${reuse_previous_request}=${FALSE}
  IF  ${reuse_previous_request}
    VAR  ${external_id} =  ${EXTERNAL_ID}
    VAR  ${body} =  ${PREVIOUS_REQUEST_BODY}
  ELSE
    ${external_id} =  Generate External ID
    Parse And Randomize Dossier Data  ${body}
    VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test
  END
  ${response} =  Send Put Request Covenant  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  $files["mainDocument"] is not None
      Upload Main Document
      ...  covenant
      ...  ${files}[mainDocument][file]
      ...  ${response}[externalId]
      ...  ${files}[mainDocument][expected_response_status]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  covenant
      ...  ${attachment}[file]
      ...  ${response}[externalId]
      ...  ${attachment}[externalId]
      ...  ${attachment}[expected_response_status]
    END
    Publication Status Should Be  covenant  ${expected_publication_status}
  END

Parse And Randomize Dossier Data
  [Arguments]  ${body}
  ${dossier_number} =  Generate Dossier Reference Number
  ${title} =  Catenate  Robot API ${dossier_number}
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  Set To Dictionary  ${body}  dossierNumber  robot-api-${dossier_number}
  Set To Dictionary  ${body}  title  ${title}
  Set To Dictionary  ${body}  departmentId  ${department_id}
  Set To Dictionary  ${body}  subjectId  ${subject_id}
  Parse Dates  ${body}
  Set Random Grounds  ${body}
  Replace Department Parties With IDs  ${body}

Replace Department Parties With IDs
  [Arguments]  ${body}
  ${parties} =  Get From Dictionary  ${body}  parties  default=[]
  VAR  @{new_parties} =  @{EMPTY}
  FOR  ${party}  IN  @{parties}
    IF  '${party}' == '<ROBOT RANDOM DEPARTMENT ID>'
      ${dept_id} =  Get Random Department ID
      Append To List  ${new_parties}  ${dept_id}
    ELSE
      Append To List  ${new_parties}  ${party}
    END
  END
  Set To Dictionary  ${body}  parties  ${new_parties}

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}  dateFrom
  Parse Text To Date  ${body}  dateTo
  Parse Text To Date  ${body}[mainDocument]  formalDate
  IF  ${body}[attachments]
    FOR  ${attachment}  IN  @{body}[attachments]
      Parse Text To Date  ${attachment}  formalDate
    END
  END

Send Put Request Covenant
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/covenant/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=Covenant PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}

Verify HAL Links Are Reachable
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Verify HAL Links Are Reachable For Dossier  covenant
