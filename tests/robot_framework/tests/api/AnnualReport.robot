*** Settings ***
Documentation       Tests for the Annual Report endpoint, utilizing a custom DataDriver reader. Actual testcases are in the file files/api/annualreport.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/annualreport.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       Annual Report Test Case
Test Tags           api  api-annualreport


*** Variables ***
${MIN_YEARS}    9
${PLUS_YEARS}   2


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

Annual Report Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    Log  ${step}[name]
    IF  '${step}[type]' == 'request'
      Create Annual Report
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create Annual Report
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
    Resolve Annual Report Year  ${body}
    VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test
  END
  ${response} =  Send Put Request Annual Report  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  $files["mainDocument"] is not None
      Upload Main Document
      ...  annual-report
      ...  ${files}[mainDocument][file]
      ...  ${response}[externalId]
      ...  ${files}[mainDocument][expected_response_status]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  annual-report
      ...  ${attachment}[file]
      ...  ${response}[externalId]
      ...  ${attachment}[externalId]
      ...  ${attachment}[expected_response_status]
    END
    Publication Status Should Be  annual-report  ${expected_publication_status}
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
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}[mainDocument]  formalDate
  IF  ${body}[attachments]
    FOR  ${attachment}  IN  @{body}[attachments]
      Parse Text To Date  ${attachment}  formalDate
    END
  END

Send Put Request Annual Report
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=AnnualReport PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}

Resolve Annual Report Year
  [Arguments]  ${body}
  ${year} =  Get From Dictionary  ${body}  year  default=${EMPTY}
  IF  '${year}' == '<ROBOT YEAR PAST>'
    ${year} =  Compute Annual Report Year Out Of Range  past
    Set To Dictionary  ${body}  year  ${year}
  ELSE IF  '${year}' == '<ROBOT YEAR FUTURE>'
    ${year} =  Compute Annual Report Year Out Of Range  future
    Set To Dictionary  ${body}  year  ${year}
  ELSE IF  '${year}' == '<ROBOT YEAR MIN>'
    ${year} =  Compute Annual Report Year Boundary  min
    Set To Dictionary  ${body}  year  ${year}
  ELSE IF  '${year}' == '<ROBOT YEAR MAX>'
    ${year} =  Compute Annual Report Year Boundary  max
    Set To Dictionary  ${body}  year  ${year}
  END

Compute Annual Report Year Out Of Range
  [Arguments]  ${direction}
  ${current_year} =  Get Current Date  result_format=%Y
  ${current_year_int} =  Convert To Integer  ${current_year}
  IF  '${direction}' == 'past'
    ${year} =  Evaluate  ${current_year_int} - (${MIN_YEARS} + 1)
  ELSE
    ${year} =  Evaluate  ${current_year_int} + (${PLUS_YEARS} + 1)
  END
  RETURN  ${year}

Compute Annual Report Year Boundary
  [Arguments]  ${edge}
  ${current_year} =  Get Current Date  result_format=%Y
  ${current_year_int} =  Convert To Integer  ${current_year}
  IF  '${edge}' == 'min'
    ${year} =  Evaluate  ${current_year_int} - ${MIN_YEARS}
  ELSE
    ${year} =  Evaluate  ${current_year_int} + ${PLUS_YEARS}
  END
  RETURN  ${year}
