*** Comments ***
# robotcode: ignore


*** Settings ***
Documentation       Tests for the WooDecision endpoint, utilizing a custom DataDriver reader. Actual testcases are in the file files/api/woodecision.yaml.
Library             DataDriver  reader_class=libraries/yaml_reader.py  file_path=files/api/woodecision.yaml
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Suite Setup         Suite Setup
Test Template       WooDecision Test Case
Test Tags           api  api-woodecision


*** Variables ***
${EXTERNAL_ID}  ${EMPTY}


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

WooDecision Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
    Log  ${step}[name]
    IF  '${step}[type]' == 'request'
      Create WooDecision
      ...  ${step}[expected_response_status]
      ...  ${step}[body]
      ...  ${step}[files]
      ...  ${step}[expected_publication_status]
      ...  ${step}[reuse_previous_request]
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create WooDecision  # robotcode: ignore
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
    VAR  ${PREVIOUS_REQUEST_BODY} =  ${body}  scope=test  # robotcode: ignore
  END
  ${response} =  Send Put Request WooDecision  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  $files["mainDocument"] is not None
      Upload Main Document
      ...  woo-decision
      ...  ${files}[mainDocument][file]
      ...  ${response}[externalId]
      ...  ${files}[mainDocument][expected_response_status]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment Document
      ...  woo-decision
      ...  ${attachment}[file]
      ...  ${response}[externalId]
      ...  ${attachment}[externalId]
      ...  ${attachment}[expected_response_status]
    END
    FOR  ${document}  IN  @{files}[documents]
      Upload Document
      ...  woo-decision
      ...  ${document}[file]
      ...  ${response}[externalId]
      ...  ${document}[externalId]
      ...  ${document}[expected_response_status]
    END
    Wait Until Keyword Succeeds  5x  2s  Publication Status Should Be  woo-decision  ${expected_publication_status}
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
  Generate Unique Document IDs  ${body}
  Set Random Grounds  ${body}  include_documents=${TRUE}

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  dateFrom
  Parse Text To Date  ${body}  dateTo
  Parse Text To Date  ${body}  previewDate
  Parse Text To Date  ${body}  publicationDate
  Parse Text To Date  ${body}[mainDocument]  formalDate
  IF  ${body}[attachments]
    FOR  ${attachment}  IN  @{body}[attachments]
      Parse Text To Date  ${attachment}  formalDate
    END
  END
  IF  ${body}[documents]
    FOR  ${document}  IN  @{body}[documents]
      Parse Text To Date  ${document}  documentDate
    END
  END

Generate Unique Document IDs
  [Arguments]  ${body}
  IF  ${body}[documents]
    FOR  ${document}  IN  @{body}[documents]
      ${document_id} =  FakerLibrary.Random Int  min=100000  max=999999
      ${document_id} =  Convert To String  ${document_id}
      IF  '${document}[documentId]' == '<ROBOT RANDOM INT>'
        Set To Dictionary  ${document}  documentId  ${document_id}
      END
    END
  END

Send Put Request WooDecision
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=WooDecision PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}

Verify WooDecision On Public
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Suite Setup Generic
  Search On Public For  ${DOSSIER_REFERENCE}  1

Verify WooDecision In Admin
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Suite Setup Generic
  Go To Admin
  Login Admin
  Select Organisation
  Search For A Publication  ${DOSSIER_REFERENCE}

Verify WooDecision Document Processing
  [Documentation]    This is not unused, it's referenced from the YAML file.
  [Arguments]  ${expected_upload_status}
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[documents][0][uploadStatus]  ${expected_upload_status}

Wait For WooDecision Document Processing
  [Documentation]    This is not unused, it's referenced from the YAML file.
  [Arguments]  ${expected_upload_status}
  Wait Until Keyword Succeeds  10x  3s  All WooDecision Documents Have Upload Status  ${expected_upload_status}

All WooDecision Documents Have Upload Status
  [Arguments]  ${expected_upload_status}
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  FOR  ${document}  IN  @{get_response.json()}[documents]
    Should Be Equal  ${document}[uploadStatus]  ${expected_upload_status}
  END

Change Document Date
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Set To Dictionary  ${PREVIOUS_REQUEST_BODY}[documents][0]  documentDate  2000-01-01
