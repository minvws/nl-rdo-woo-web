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
${EXTERNAL_ID}                  ${EMPTY}
${STORED_DOCUMENT_EXTERNAL_ID}  ${EMPTY}


*** Test Cases ***
Testcases     placeholder_arg


*** Keywords ***
Suite Setup
  Suite Setup API

WooDecision Test Case
  [Arguments]  ${steps}
  FOR  ${step}  IN  @{steps}
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
  Resolve Stored External Id References  ${body}

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

Store WooDecision Document Nr
  [Documentation]    This is not unused, it's referenced from the YAML file.
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  VAR  ${STORED_DOCUMENT_NR} =  ${get_response.json()}[documents][0][documentNr]  scope=test

Change Document ID
  [Documentation]    This is not unused, it's referenced from the YAML file.
  ${new_document_id} =  FakerLibrary.Random Int  min=1  max=99999
  ${new_document_id} =  Convert To String  ${new_document_id}
  Set To Dictionary  ${PREVIOUS_REQUEST_BODY}[documents][0]  documentId  ${new_document_id}

Verify WooDecision Document Nr Changed
  [Documentation]    This is not unused, it's referenced from the YAML file.
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  VAR  ${document_nr} =  ${get_response.json()}[documents][0][documentNr]
  Should Not Be Equal  ${document_nr}  ${STORED_DOCUMENT_NR}

Store WooDecision Document External Id
  [Documentation]    This is not unused, it's referenced from the YAML file.
  ${get_response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  VAR  ${STORED_DOCUMENT_EXTERNAL_ID} =  ${get_response.json()}[documents][0][externalId]  scope=test

Resolve Stored External Id References
  [Documentation]    It replaces any occurrence of <STORED DOCUMENT EXTERNAL ID> in the refersTo lists with the actual stored external id from the previous request,
  ...    allowing to link documents together across multiple requests.
  [Arguments]  ${body}
  IF  ${body}[documents]
    FOR  ${document}  IN  @{body}[documents]
      VAR  ${refers_to} =  ${document}[refersTo]
      IF  $refers_to and '<STORED DOCUMENT EXTERNAL ID>' in $refers_to
        ${new_refers_to} =  Evaluate
        ...  [$STORED_DOCUMENT_EXTERNAL_ID if v == '<STORED DOCUMENT EXTERNAL ID>' else v for v in $refers_to]
        Set To Dictionary  ${document}  refersTo  ${new_refers_to}
      END
    END
  END

Verify HAL Links Are Reachable
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Verify HAL Links Are Reachable For Dossier  woo-decision  has_documents=${TRUE}

Deeply Validate WooDecision On Public
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Suite Setup Generic
  Navigate To Dossier On Public  ${DOSSIER_REFERENCE}
  Verify WooDecision Dossier Metadata On Public
  Download WooDecision Inventory
  Download WooDecision Main Document
  Download WooDecision Documents
  Bulk Download WooDecision Documents

Verify WooDecision Dossier Metadata On Public
  ${reference_lower} =  Convert To Lower Case  ${DOSSIER_REFERENCE}
  Get Text  //*[@data-e2e-name="dossier-metadata-title"]  contains  ${DOSSIER_REFERENCE}
  Get Text  //*[@data-e2e-name="dossier-metadata-number"]  contains  ${reference_lower}

Download WooDecision Inventory
  Generic Download Click  //*[@data-e2e-name="download-inventory-file-link"]

Download WooDecision Main Document
  Click  //*[@data-e2e-name="main-document-detail-link"]
  Generic Download Click  //a[@data-e2e-name="download-file-link"]
  Go Back

Download WooDecision Documents
  ${doc_count} =  Get Element Count  //*[@data-e2e-name="tabs-documenten-content-1"]//tbody//tr
  FOR  ${i}  IN RANGE  ${doc_count}
    ${index} =  Evaluate  ${i} + 1
    Click  (//*[@data-e2e-name="tabs-documenten-content-1"]//tbody//tr)[${index}]//td[3]//a
    Generic Download Click  //a[@data-e2e-name="download-file-link"]
    Go Back
  END

Bulk Download WooDecision Documents
  ${dossier_url} =  Get Url
  Click  //*[@data-e2e-name="download-documents-button"]
  Wait Until Keyword Succeeds  5x  2s  Get Element  //a[@data-e2e-name="download-file-link"]
  Generic Download Click  //a[@data-e2e-name="download-file-link"]  zip
  Go To  ${dossier_url}

UploadStatus Is No Upload Required
  [Documentation]    This is not unused, it's referenced from the YAML file.
  Verify WooDecision Document Processing  no_upload_required
