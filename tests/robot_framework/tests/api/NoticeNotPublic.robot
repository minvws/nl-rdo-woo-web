*** Settings ***
Documentation       Tests for the Advice endpoint using NoticeNotPublic instead of a main document,
...                 utilizing a custom DataDriver reader. Actual testcases are in the file
...                 files/api/noticenotpublic.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/noticenotpublic.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       Notice Not Public Test Case
Test Tags           api  api-notice-not-public


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

Notice Not Public Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    Log  ${step}[name]
    IF  '${step}[type]' == 'request'
      Create Notice Not Public
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create Notice Not Public
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
  ${response} =  Send Put Request Notice Not Public  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  advice
      ...  ${attachment}[file]
      ...  ${response}[externalId]
      ...  ${attachment}[externalId]
      ...  ${attachment}[expected_response_status]
    END
    Publication Status Should Be  advice  ${expected_publication_status}
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
  Set Random Notice Not Public Grounds  ${body}

Set Random Notice Not Public Grounds
  [Arguments]  ${body}
  ${notice} =  Get From Dictionary  ${body}  noticeNotPublic  default=${NONE}
  IF  $notice is not None
    ${needs_randomize} =  Evaluate  $notice['grounds'] == ['<ROBOT RANDOMIZE>']
    IF  ${needs_randomize}
      ${random_grounds} =  Get Random Grounds
      Set To Dictionary  ${body}[noticeNotPublic]  grounds  ${random_grounds}
    END
  END
  ${attachments} =  Get From Dictionary  ${body}  attachments  default=[]
  FOR  ${attachment}  IN  @{attachments}
    ${needs_randomize} =  Evaluate  $attachment['grounds'] == ['<ROBOT RANDOMIZE>']
    IF  ${needs_randomize}
      ${random_grounds} =  Get Random Grounds
      Set To Dictionary  ${attachment}  grounds  ${random_grounds}
    END
  END

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}  dossierDate
  ${main_doc} =  Get From Dictionary  ${body}  mainDocument  default=${NONE}
  IF  $main_doc is not None
    Parse Text To Date  ${body}[mainDocument]  formalDate
  END
  ${notice} =  Get From Dictionary  ${body}  noticeNotPublic  default=${NONE}
  IF  $notice is not None
    Parse Text To Date  ${body}[noticeNotPublic]  formalDate
  END
  IF  ${body}[attachments]
    FOR  ${attachment}  IN  @{body}[attachments]
      Parse Text To Date  ${attachment}  formalDate
    END
  END

Verify HAL Links Are Reachable For Advice
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Verify HAL Links Are Reachable For Dossier  advice

Send Put Request Notice Not Public
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/advice/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=NoticeNotPublic PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}
