*** Comments ***
# robocop: off=no-suite-variable,no-global-variable


*** Settings ***
Documentation       Tests for the Subjects API
Library             RequestsLibrary
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/TestData.resource


*** Variables ***
${ORGANISATION_ID}  ${EMPTY}
${CURRENT_DATE}     ${EMPTY}
${RESPONSE}         ${EMPTY}
${EXTERNAL_ID}      ${EMPTY}
${API_ALIAS}        publication_api


*** Keywords ***
Suite Setup API
  Set Tenant Context  %{TENANT}
  Create Session
  Retrieve Organisation ID
  Set DateTime Variables

Create Session
  [Arguments]  ${alias}=publication_api
  VAR  @{client_certs} =  ${CLIENT_CERT}  ${CLIENT_KEY}
  IF  '${ENVIRONMENT}' == 'local'
    Set Environment Variable  REQUESTS_CA_BUNDLE  ${CA_BUNDLE}
    Create Client Cert Session
    ...  url=${URL_API}
    ...  alias=${alias}
    ...  client_certs=@{client_certs}
    ...  verify=${CA_BUNDLE}
    ...  debug=1
  ELSE IF  '${ENVIRONMENT}' == 'test' or '${ENVIRONMENT}' == 'acc'
    Create Client Cert Session
    ...  url=${URL_API}
    ...  alias=${alias}
    ...  client_certs=@{client_certs}
    ...  verify=True
    ...  debug=1
  ELSE
    Fatal Error  Env var ENVIRONMENT returned unsupported value '${ENVIRONMENT}'.
  END

Create Session For Tenant
  [Documentation]  Creates a named mTLS session for the given tenant.
  ...  Calls Set Tenant Context to configure globals, then delegates to Create Session.
  ...  Both sessions coexist independently once created.
  [Arguments]  ${tenant}  ${alias}
  Set Tenant Context  ${tenant}
  Create Session  ${alias}

Retrieve Organisation ID
  ${response} =  GET On Session  alias=${API_ALIAS}  url=${URL_API}/api/publication/v1/organisation
  ${organisation} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Organisation
  VAR  ${ORGANISATION_ID} =  ${organisation}[id]  scope=suite

Get Department ID
  ${response} =  GET On Session  alias=${API_ALIAS}  url=${URL_API}/api/publication/v1/department
  ${department} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Department 1
  RETURN  ${department}[id]

Get Random Department ID
  ${department_ids} =  Get Random Department IDs  1
  RETURN  ${department_ids}[0]

Get Random Department IDs
  [Arguments]  ${count}=1
  ${response} =  GET On Session  alias=${API_ALIAS}  url=${URL_API}/api/publication/v1/department
  ${departments} =  Set Variable  ${response.json()}
  @{random_departments} =  FakerLibrary.Random Elements  ${departments}  ${count}  unique=True
  @{department_ids} =  Create List
  FOR  ${department}  IN  @{random_departments}
    Append To List  ${department_ids}  ${department}[id]
  END
  RETURN  @{department_ids}

Get Subject ID
  ${response} =  GET On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ${subject} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Subject
  RETURN  ${subject}[id]

Get Object From Json By Attribute
  [Arguments]  ${json}  ${attribute_name}  ${attribute_value}
  FOR  ${element}  IN  @{json}
    VAR  ${value} =  ${element}[${attribute_name}]
    IF  $value == $attribute_value  RETURN  ${element}
  END
  Fatal Error  No Object found with ${attribute_name} '${attribute_value}'

Upload File
  [Arguments]
  ...  ${file_location}
  ...  ${dossier_type}
  ...  ${dossier_id}
  ...  ${document_type}
  ...  ${expected_response_status}
  ...  ${document_id}=${EMPTY}
  ${binary_file} =  Get Binary File  ${file_location}
  VAR  &{headers} =  Content-Type=application/octet-stream
  IF  '${document_id}' != '${EMPTY}'
    VAR  ${document_id_snippet} =  /external/${document_id}
  ELSE
    VAR  ${document_id_snippet} =  ${EMPTY}
  END
  ${put_response} =  PUT On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/${dossier_type}/external/${dossier_id}/uploads/${document_type}${document_id_snippet}
  ...  data=${binary_file}
  ...  headers=${headers}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=Upload ${document_type} returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response}

Get Date Minus
  [Arguments]  ${offset}  ${base_date}=${CURRENT_DATE}
  ${date} =  Subtract Time From Date  ${base_date}  ${offset}  %Y-%m-%d
  RETURN  ${date}

Get Date Plus
  [Arguments]  ${offset}  ${base_date}=${CURRENT_DATE}
  ${date} =  Add Time To Date  ${base_date}  ${offset}  %Y-%m-%d
  RETURN  ${date}

Get Random Grounds
  VAR  @{grounds} =
  ...  5.1.1a
  ...  5.1.1b
  ...  5.1.1c
  ...  5.1.1d
  ...  5.1.1e
  ...  5.1.2a
  ...  5.1.2b
  ...  5.1.2c
  ...  5.1.2d
  ...  5.1.2e
  ...  5.1.2f
  ...  5.1.2g
  ...  5.1.2h
  ...  5.1.2i
  ...  5.1.5
  ...  5.2
  ...  5.4
  ...  10.1a
  ...  10.1b
  ...  10.1c
  ...  10.1d
  ...  10.2a
  ...  10.2b
  ...  10.2c
  ...  10.2d
  ...  10.2e
  ...  10.2g
  ...  11.1
  ${count} =  FakerLibrary.Random Int  0  5
  @{random_grounds} =  FakerLibrary.Random Elements  ${grounds}  ${count}  unique=True
  RETURN  @{random_grounds}

Generate External ID
  ${id} =  FakerLibrary.Isbn 10
  VAR  ${EXTERNAL_ID} =  ${id}  scope=suite
  RETURN  ${EXTERNAL_ID}

Generate Main Document
  [Arguments]  ${file_location}=${TEST_DATA_ROOT}/dummy.txt  ${type}=${EMPTY}
  ${filename} =  Fetch From Right  ${file_location}  /
  ${formal_date} =  Get Date Minus  1 day
  ${grounds} =  Get Random Grounds
  VAR  ${language} =  NLD
  VAR  &{main_document} =
  ...  fileName=${filename}
  ...  formalDate=${formal_date}
  ...  grounds=${grounds}
  ...  language=${language}
  IF  '${type}' != '${EMPTY}'
    Set To Dictionary  ${main_document}  type  ${type}
  END
  RETURN  ${main_document}

Generate Attachment
  [Arguments]  ${allowed_types}
  ${file_name} =  FakerLibrary.Sentence
  ${file_name} =  Catenate  ${file_name}txt
  ${type} =  FakerLibrary.Random Element  ${allowed_types}
  ${formal_date} =  Get Date Minus  1 day
  ${grounds} =  Get Random Grounds
  ${external_id} =  FakerLibrary.Uuid 4
  VAR  ${language} =  NLD
  VAR  &{main_document} =
  ...  fileName=${file_name}
  ...  formalDate=${formal_date}
  ...  grounds=${grounds}
  ...  language=${language}
  ...  type=${type}
  ...  externalId=${external_id}
  RETURN  ${main_document}

Generate Attachments
  [Arguments]  ${allowed_types}
  VAR  @{attachments} =  @{EMPTY}
  WHILE  True  limit=5  on_limit=pass
    ${attachment} =  Generate Attachment  ${allowed_types}
    Append To List  ${attachments}  ${attachment}
  END
  RETURN  ${attachments}

We Dont Upload The Main Document
  Log  We explicitly don't upload the main document (because its not working yet)  level=WARN

Its Not Published Yet
  Log  ${RESPONSE}[publicationDate]
  Log  ${RESPONSE}[status]
  Should Be Equal  ${RESPONSE}[status]  concept

Parse Text To Date
  [Arguments]  ${dict}  ${field}
  ${match} =  Get Regexp Matches  ${dict}[${field}]  <ROBOT DATE (?P<offset>[+-]+[0-9]+)>  offset
  IF  ${match}
    ${date} =  Get Current Date  result_format=%Y-%m-%d  increment=${match[0]} days
    Set To Dictionary  ${dict}  ${field}  ${date}
  END

Set Random Grounds
  [Arguments]  ${body}  ${include_documents}=${FALSE}
  ${needs_randomize} =  Evaluate  $body['mainDocument']['grounds'] == ['<ROBOT RANDOMIZE>']
  IF  ${needs_randomize}
    ${random_grounds} =  Get Random Grounds
    Set To Dictionary  ${body}[mainDocument]  grounds  ${random_grounds}
  END
  IF  ${include_documents}
    ${documents} =  Get From Dictionary  ${body}  documents  default=[]
    FOR  ${document}  IN  @{documents}
      ${needs_randomize} =  Evaluate  $document['grounds'] == ['<ROBOT RANDOMIZE>']
      IF  ${needs_randomize}
        ${random_grounds} =  Get Random Grounds
        Set To Dictionary  ${document}  grounds  ${random_grounds}
      END
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

Upload Main Document
  [Arguments]  ${dossier_type}  ${file_location}  ${dossier_id}  ${expected_response_status}=204
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=${dossier_type}
  ...  dossier_id=${dossier_id}
  ...  document_type=main-document
  ...  expected_response_status=${expected_response_status}

Upload Attachment Document
  [Arguments]  ${dossier_type}  ${file_location}  ${dossier_id}  ${attachment_id}  ${expected_response_status}=204
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=${dossier_type}
  ...  dossier_id=${dossier_id}
  ...  document_type=attachment
  ...  document_id=${attachment_id}
  ...  expected_response_status=${expected_response_status}

Upload Document
  [Arguments]  ${dossier_type}  ${file_location}  ${dossier_id}  ${document_id}  ${expected_response_status}=204
  Upload File
  ...  file_location=${file_location}
  ...  dossier_type=${dossier_type}
  ...  dossier_id=${dossier_id}
  ...  document_type=document
  ...  document_id=${document_id}
  ...  expected_response_status=${expected_response_status}

Publication Status Should Be
  [Arguments]  ${dossier_type}  ${expected_status}
  ${get_response} =  GET On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/${dossier_type}/external/${EXTERNAL_ID}
  Should Be Equal  ${get_response.json()}[status]  ${expected_status}

Send Put Request WooDecision
  [Arguments]  ${external_id}  ${body}  ${expected_response_status}  ${alias}=publication_api
  ${put_response} =  PUT On Session
  ...  alias=${alias}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${external_id}
  ...  json=${body}
  ...  expected_status=any
  Should Be True
  ...  ${put_response.status_code} == ${expected_response_status}
  ...  msg=WooDecision PUT returned ${put_response.status_code} while expecting ${expected_response_status}
  RETURN  ${put_response.json()}
