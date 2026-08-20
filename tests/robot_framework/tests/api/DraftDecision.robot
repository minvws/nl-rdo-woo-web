*** Settings ***
Documentation       Tests for the DraftDecision endpoint, utilizing a custom DataDriver reader. Actual testcases are in the file files/api/draftdecision.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/draftdecision.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       DraftDecision Test Case
Test Tags           api  api-draftdecision


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API
  Check DraftDecision Feature Enabled

Check DraftDecision Feature Enabled
  [Documentation]    Checks if DraftDecision endpoint is enabled. Skips suite if feature returns 403 not enabled.
  TRY
    ${response} =  GET On Session
    ...  alias=publication_api
    ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/draft-decision
    ...  expected_status=any
    IF  ${response.status_code} == 403
      ${body} =  Convert To String  ${response.text}
      IF  'feature is not enabled' in '${body}'
        Skip  DraftDecision feature is not enabled in this environment
      END
    END
  EXCEPT
    Log  Could not verify DraftDecision feature status, continuing with tests
  END

DraftDecision Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    IF  '${step}[type]' == 'request'
      Create DraftDecision
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create DraftDecision
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
    Parse And Randomize DraftDecision Data  ${body}
    VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test
  END
  ${response} =  Send Put Request DraftDecision  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  $files["mainDocument"] is not None
      Upload Main Document
      ...  draft-decision
      ...  ${files}[mainDocument][file]
      ...  ${response}[externalId]
      ...  ${files}[mainDocument][expected_response_status]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  draft-decision
      ...  ${attachment}[file]
      ...  ${response}[externalId]
      ...  ${attachment}[externalId]
      ...  ${attachment}[expected_response_status]
    END
    Publication Status Should Be  draft-decision  ${expected_publication_status}
  END

Parse And Randomize DraftDecision Data
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

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}  dossierDate
  Parse Text To Date  ${body}[mainDocument]  formalDate
  IF  ${body}[attachments]
    FOR  ${attachment}  IN  @{body}[attachments]
      Parse Text To Date  ${attachment}  formalDate
    END
  END

Send Put Request DraftDecision
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/draft-decision/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=DraftDecision PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}

Verify HAL Links Are Reachable
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Verify HAL Links Are Reachable For Dossier  draft-decision
