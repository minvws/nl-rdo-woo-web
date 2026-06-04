*** Settings ***
Documentation       Tests for the Complaint Judgement endpoint, utilizing a custom DataDriver reader.
...                 Actual testcases are in the file files/api/complaintjudgement.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/complaintjudgement.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       Complaint Judgement Test Case
Test Tags           api  api-complaintjudgement


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

Complaint Judgement Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    Log  ${step}[name]
    IF  '${step}[type]' == 'request'
      Create Complaint Judgement
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create Complaint Judgement
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
  ${response} =  Send Put Request Complaint Judgement  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  $files["mainDocument"] is not None
      Upload Main Document
      ...  complaint-judgement
      ...  ${files}[mainDocument][file]
      ...  ${response}[externalId]
      ...  ${files}[mainDocument][expected_response_status]
    END
    Publication Status Should Be  complaint-judgement  ${expected_publication_status}
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

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  dossierDate
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}[mainDocument]  formalDate

Send Put Request Complaint Judgement
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/complaint-judgement/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=Complaint judgement PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}
