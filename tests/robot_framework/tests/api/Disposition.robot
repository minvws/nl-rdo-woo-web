*** Settings ***
Documentation       Tests for the Disposition endpoint, utilizing a custom DataDriver reader. Actual testcases are in the file files/api/disposition.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/disposition.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Disposition.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       Disposition Test Case
Test Tags           api  api-disposition


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

Disposition Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    Log  ${step}[name]
    IF  '${step}[type]' == 'request'
      Create Disposition
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create Disposition
  [Arguments]  ${expected_response_status}  ${body}  ${files}  ${expected_publication_status}
  ${external_id} =  Generate External ID
  Parse And Randomize Disposition Data  ${body}
  ${response} =  Send Put Request Disposition  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  '${files}[mainDocument]' != 'None'
      Upload Main Document  disposition  ${files}[mainDocument]  E:${response}[externalId]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  disposition
      ...  ${attachment}[file]
      ...  E:${response}[externalId]
      ...  E:${attachment}[externalId]
    END
    Publication Status Should Be  disposition  ${expected_publication_status}
  END

Parse And Randomize Disposition Data
  [Arguments]  ${body}
  ${dossier_number} =  Generate Dossier Reference Number
  ${title} =  Catenate  Robot API ${dossier_number}
  ${department_id} =  Get Department ID
  ${subject_id} =  Get Subject ID
  Set To Dictionary  ${body}  dossierNumber  ${dossier_number}
  Set To Dictionary  ${body}  title  ${title}
  Set To Dictionary  ${body}  departmentId  ${department_id}
  Set To Dictionary  ${body}  subjectId  ${subject_id}
  Parse Dates  ${body}
  Set Random Grounds  ${body}

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

Send Put Request Disposition
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=%{URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/disposition/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=Disposition PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}
