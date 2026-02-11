*** Comments ***
# robocop: off=no-suite-variable,no-global-variable


*** Settings ***
Documentation       Tests for the Subjects API
Library             RequestsLibrary
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/TestData.resource


*** Variables ***
${BASE_URL}         ${EMPTY}
${ORGANISATION_ID}  ${EMPTY}
${CURRENT_DATE}     ${EMPTY}
${RESPONSE}         ${EMPTY}


*** Keywords ***
Suite Setup API
  Create Session
  Retrieve Organisation ID
  Set DateTime Variables
  Set Current Date RFC 3339 As Today

Create Session
  IF  '%{ENVIRONMENT}' == 'docker'
    VAR  ${BASE_URL} =  https://publication-api-woo.local  scope=global
    VAR  ${client_cert} =  ./certs/generated/client-vws.pem
    VAR  ${client_key} =  ./certs/generated/private/client-vws.key
    VAR  ${ca_bundle} =  ./certs/generated/client-bundle.pem
  ELSE
    VAR  ${BASE_URL} =  https://localhost:8443  scope=global
    VAR  ${client_cert} =  ../../docker/certs/generated/client-vws.pem
    VAR  ${client_key} =  ../../docker/certs/generated/private/client-vws.key
    VAR  ${ca_bundle} =  ../../docker/certs/generated/client-bundle.pem
  END
  VAR  @{client_certs} =  ${client_cert}  ${client_key}
  Set Environment Variable  REQUESTS_CA_BUNDLE  ${ca_bundle}
  Create Client Cert Session
  ...  url=${BASE_URL}
  ...  alias=publication_api
  ...  client_certs=@{client_certs}
  ...  verify=${ca_bundle}
  ...  debug=1

Retrieve Organisation ID
  ${response} =  GET On Session  alias=publication_api  url=${BASE_URL}/api/publication/v1/organisation
  ${organisation} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Organisation
  VAR  ${ORGANISATION_ID} =  ${organisation}[id]  scope=suite

Set Current Date RFC 3339 As Today
  ${today_rfc} =  Convert Date To RFC 3339  ${CURRENT_DATE}
  VAR  ${TODAY} =  ${today_rfc}  scope=suite

Convert Date To RFC 3339
  [Arguments]  ${date}
  ${formatted_date} =  Convert Date  ${date}  result_format=%Y-%m-%dT%H:%M:%S.%fZ
  # Only take first 3 millisecs.
  ${rfc_date} =  Evaluate  "${formatted_date[:-7]}${formatted_date[-7:-4]}Z"
  RETURN  ${rfc_date}

Get Department ID
  ${response} =  GET On Session  alias=publication_api  url=${BASE_URL}/api/publication/v1/department
  ${department} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Department 1
  RETURN  ${department}[id]

Get Subject ID
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ${subject} =  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Subject
  RETURN  ${subject}[id]

Get Prefix
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/prefix
  ${prefix} =  Get Object From Json By Attribute  ${response.json()}  prefix  E2E-A
  RETURN  ${prefix}[prefix]

Get Object From Json By Attribute
  [Arguments]  ${json}  ${attribute_name}  ${attribute_value}
  FOR  ${element}  IN  @{json}
    IF  '${element}[${attribute_name}]' == '${attribute_value}'
      RETURN  ${element}
    END
  END
  Fatal Error  No Object found with ${attribute_name} '${attribute_value}'

Get Random Attachment Type
  VAR  @{attachment_types} =
  ...  c_002fc258
  ...  c_03c52ba0
  ...  c_056a75e1
  ...  c_0670bae1
  ...  c_06a67c95
  ...  c_0e425c23
  ...  c_1cf18f83
  ...  c_2977c34f
  ...  c_2ab17960
  ...  c_2aedadff
  ...  c_2c0438f4
  ...  c_30e8b503
  ...  c_386e74cb
  ...  c_38ba44de
  ...  c_3d782f30
  ...  c_3eb5572a
  ...  c_42e406dd
  ...  c_45be34e9
  ...  c_4efe1293
  ...  c_4f50ca9c
  ...  c_566a4430
  ...  c_5824891d
  ...  c_5b1055aa
  ...  c_61e3099a
  ...  c_6d494ab6
  ...  c_6f49bf34
  ...  c_7652d853
  ...  c_7eba29ad
  ...  c_8b92eab4
  ...  c_9376c730
  ...  c_97f44ea5
  ...  c_99d3e284
  ...  c_9ecc0007
  ...  c_a17ef403
  ...  c_a1dae55d
  ...  c_a40458df
  ...  c_a55b7649
  ...  c_a6f44748
  ...  c_acb44d77
  ...  c_aebfec50
  ...  c_bf0f9c95
  ...  c_c1956ef0
  ...  c_c2f56984
  ...  c_cccba364
  ...  c_d4a4792f
  ...  c_d506b718
  ...  c_d943ca24
  ...  c_dad2a6ed
  ...  c_de27ae7a
  ...  c_df2cb56e
  ...  c_dfa0ff1f
  ...  c_ef935990
  ...  c_f1652921
  ...  c_f7dc55d9
  ...  c_f90465b3
  ...  c_fbaa7e4b
  ...  overig
  ...  ww_a2azo7q
  ...  ww_jc6woe9
  ${random_attachment_type} =  FakerLibrary.Random Element  ${attachment_types}
  RETURN  ${random_attachment_type}

Upload File
  [Arguments]  ${dossier_type}  ${dossier_id}  ${upload_id}  ${file_location}  ${document_type}
  ${binary_file} =  Get Binary File  ${file_location}
  VAR  &{headers} =  Content-Type=application/octet-stream
  ${put_response} =  PUT On Session
  ...  alias=publication_api
  ...  url=${BASE_URL}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/${dossier_type}/${dossier_id}/uploads/${document_type}/${upload_id}
  ...  data=${binary_file}
  ...  headers=${headers}
  RETURN  ${put_response}

Get Date Minus
  [Arguments]  ${offset}  ${base_date}=${CURRENT_DATE}
  ${date} =  Subtract Time From Date  ${base_date}  ${offset}
  ${date_iso} =  Convert Date To RFC 3339  ${date}
  RETURN  ${date_iso}

Get Date Plus
  [Arguments]  ${offset}  ${base_date}=${CURRENT_DATE}
  ${date} =  Add Time To Date  ${base_date}  ${offset}
  ${date_iso} =  Convert Date To RFC 3339  ${date}
  RETURN  ${date_iso}

Get Random Ground
  VAR  @{grounds} =
  ...  ground1
  ...  ground2
  ${random_ground} =  FakerLibrary.Random Element  ${grounds}
  RETURN  ${random_ground}

Generate External ID
  ${id} =  FakerLibrary.Isbn 10
  VAR  ${EXTERNAL_ID} =  E:${id}  scope=suite
  RETURN  ${EXTERNAL_ID}

Generate Main Document
  [Arguments]  ${file_location}=${TEST_DATA_ROOT}/dummy.txt  ${type}=${EMPTY}
  ${filename} =  Fetch From Right  ${file_location}  /
  ${internal_reference} =  FakerLibrary.Sentence
  ${formal_date} =  Get Date Minus  1 day
  VAR  ${language} =  Dutch
  VAR  &{main_document} =
  ...  filename=${filename}
  ...  formalDate=${formal_date}
  ...  grounds=@{EMPTY}
  ...  internalReference=${internal_reference}
  ...  language=${language}
  IF  '${type}' != '${EMPTY}'
    Set To Dictionary  ${main_document}  type  ${type}
  END
  RETURN  ${main_document}

Generate Attachment
  ${file_name} =  FakerLibrary.Sentence
  ${file_name} =  Catenate  ${file_name}.txt
  ${internal_reference} =  FakerLibrary.Sentence
  ${attachment_type} =  Get Random Attachment Type
  ${formal_date} =  Get Date Minus  1 day
  ${external_id} =  FakerLibrary.Uuid 4
  VAR  ${language} =  Dutch
  VAR  &{main_document} =
  ...  fileName=${file_name}
  ...  formalDate=${formal_date}
  ...  grounds=@{EMPTY}
  ...  internalReference=${internal_reference}
  ...  language=${language}
  ...  type=${attachment_type}
  ...  externalId=${external_id}
  RETURN  ${main_document}

Generate Attachments
  VAR  @{attachments} =  @{EMPTY}
  WHILE  True  limit=5  on_limit=pass
    ${attachment} =  Generate Attachment
    Append To List  ${attachments}  ${attachment}
  END
  RETURN  ${attachments}

We Dont Upload The Main Document
  Log  We explicitly don't upload the main document (because its not working yet)  level=WARN

Its Not Published Yet
  Log  ${RESPONSE}[publicationDate]
  Log  ${RESPONSE}[status]
  Should Be Equal  ${RESPONSE}[status]  concept
