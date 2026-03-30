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
    ELSE IF  '${step}[type]' == 'keyword'
      Run Keyword  ${step}[keyword]  @{step["args"]}
    END
  END

Create WooDecision
  [Arguments]  ${expected_response_status}  ${body}  ${files}  ${expected_publication_status}
  ${external_id} =  Generate External ID
  Parse And Randomize Dossier Data  ${body}
  ${response} =  Send Put Request WooDecision  ${external_id}  ${body}  ${expected_response_status}
  IF  '${expected_response_status}' == '200'
    IF  '${files}[mainDocument]' != 'None'
      Upload Main Document  ${files}[mainDocument]  E:${response}[externalId]
    END
    FOR  ${attachment}  IN  @{files}[attachments]
      Upload Attachment  ${attachment}[file]  E:${response}[externalId]  E:${attachment}[externalId]
    END
    FOR  ${document}  IN  @{files}[documents]
      Upload Document  ${document}[file]  E:${response}[externalId]  E:${document}[externalId]
    END
    Publication Status Should Be  ${expected_publication_status}
  END

Parse And Randomize Dossier Data
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
  Generate Unique Document IDs  ${body}

Parse Dates
  [Arguments]  ${body}
  Parse Text To Date  ${body}  dossierDateFrom
  Parse Text To Date  ${body}  dossierDateTo
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
      Parse Text To Date  ${document}  date
    END
  END

Generate Unique Document IDs
  [Arguments]  ${body}
  IF  ${body}[documents]
    FOR  ${document}  IN  @{body}[documents]
      ${document_nr} =  FakerLibrary.Random Int  min=100000  max=999999
      ${document_nr} =  Convert To String  ${document_nr}
      Set To Dictionary  ${document}  documentNr  ${document_nr}
      Set To Dictionary  ${document}  documentId  ${document_nr}
    END
  END

Send Put Request WooDecision
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=WooDecision PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}

Upload Main Document
  [Arguments]  ${file_location}  ${dossier_id}
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=woo-decision
  ...  dossier_id=${dossier_id}
  ...  document_type=main-document

Upload Attachment
  [Arguments]  ${file_location}  ${dossier_id}  ${attachment_id}
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=woo-decision
  ...  dossier_id=${dossier_id}
  ...  document_type=attachment
  ...  document_id=${attachment_id}

Upload Document
  [Arguments]  ${file_location}  ${dossier_id}  ${document_id}
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=woo-decision
  ...  dossier_id=${dossier_id}
  ...  document_type=document
  ...  document_id=${document_id}

Publication Status Should Be
  [Arguments]  ${expected_status}
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[status]  ${expected_status}

Verify WooDecision On Public
  Suite Setup Generic
  Search On Public For  ${DOSSIER_REFERENCE}  1

Verify WooDecision In Admin
  Suite Setup Generic
  Go To Admin
  Login Admin
  Select Organisation
  Search For A Publication  ${DOSSIER_REFERENCE}

Verify WooDecision Document Processing
  [Arguments]  ${expected_upload_status}
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[documents][0][uploadStatus]  ${expected_upload_status}
