*** Settings ***
Documentation       Keywords for uploading Excel main documents and checking their table preview on public.
Resource            API.resource


*** Keywords ***
Replace Main Document
  [Documentation]    This is not unused, it's referenced from yaml files.
  [Arguments]  ${dossier_type}  ${file_location}
  Upload Main Document  ${dossier_type}  ${file_location}  ${EXTERNAL_ID}

Main Document Upload Should Be Rejected
  [Documentation]    This is not unused, it's referenced from yaml files.
  [Arguments]  ${dossier_type}  ${file_location}  ${expected_reason}
  ${response} =  Upload Main Document  ${dossier_type}  ${file_location}  ${EXTERNAL_ID}  422
  ${messages} =  Evaluate  [violation['message'] for violation in $response.json()['violations']]
  List Should Contain Value  ${messages}  ${expected_reason}

Main Document Spreadsheet Preview Should Show Column
  [Documentation]    This is not unused, it's referenced from yaml files.
  [Arguments]  ${dossier_type}  ${column}
  Create Public Session
  ${links} =  Get Main Document Links  ${dossier_type}
  ${path} =  Remove String Using Regexp  ${links}[public][href]  ^https?://[^/]+
  # The preview table is built in the background after upload
  Wait Until Keyword Succeeds  20 times  3s  Spreadsheet Preview Column Check  ${path}  ${column}

Spreadsheet Preview Column Check
  [Arguments]  ${path}  ${column}
  ${response} =  GET On Session  alias=public  url=${path}
  Should Contain  ${response.text}  data-e2e-name="spreadsheet-preview"
  Should Contain  ${response.text}  >${column}</th>

Main Document Download Should Match File
  [Documentation]    This is not unused, it's referenced from yaml files.
  [Arguments]  ${dossier_type}  ${file_location}
  Create Public Session
  ${links} =  Get Main Document Links  ${dossier_type}
  ${path} =  Remove String Using Regexp  ${links}[file][href]  ^https?://[^/]+
  ${expected_content} =  Get Binary File  ${file_location}
  Wait Until Keyword Succeeds  20 times  3s  Download Content Check  ${path}  ${expected_content}

Download Content Check
  [Arguments]  ${path}  ${expected_content}
  ${response} =  GET On Session  alias=public  url=${path}
  Should Be Equal  ${response.content}  ${expected_content}  msg=Downloaded file differs from ${path}

Get Main Document Links
  [Arguments]  ${dossier_type}
  ${response} =  GET On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/${dossier_type}/external/${EXTERNAL_ID}
  RETURN  ${response.json()}[mainDocument][_links]
