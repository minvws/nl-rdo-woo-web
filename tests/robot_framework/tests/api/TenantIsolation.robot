*** Comments ***
# robocop: off=no-test-variable


*** Settings ***
Documentation       Cross-tenant isolation tests verifying data created on one tenant
...                 is not visible on another tenant.
Resource            ../../resources/API.resource
Suite Setup         Setup Cross-Tenant API Sessions
Test Tags           api  tenant-isolation  skip-test-acc


*** Variables ***
${ORG_ID_MINVWS}    ${EMPTY}
${ORG_ID_MINFIN}    ${EMPTY}


*** Test Cases ***
Dossier Created On Minvws Is Not Searchable On Minfin Public
  ${unique_ref} =  FakerLibrary.Uuid 4
  Create Dossier On Tenant  alias=api_minvws  org_id=${ORG_ID_MINVWS}  reference=${unique_ref}  tenant=minvws
  # Verify it is findable via the minvws public search
  Set Tenant Context  minvws
  Search On Public For  ${unique_ref}  2 resultaten
  # Switch to minfin public — dossier must NOT appear
  Set Tenant Context  minfin
  Search On Public For  ${unique_ref}  0 resultaten


*** Keywords ***
Setup Cross-Tenant API Sessions
  Create Session For Tenant  minvws  api_minvws
  VAR  ${API_ALIAS} =  api_minvws  scope=suite
  Retrieve Organisation ID
  VAR  ${ORG_ID_MINVWS} =  ${ORGANISATION_ID}  scope=suite
  Create Session For Tenant  minfin  api_minfin
  VAR  ${API_ALIAS} =  api_minfin  scope=suite
  Retrieve Organisation ID
  VAR  ${ORG_ID_MINFIN} =  ${ORGANISATION_ID}  scope=suite
  Open Browser And BaseUrl

Create Dossier On Tenant
  [Documentation]  Creates and publishes a minimal woo-decision dossier on the given tenant.
  [Arguments]  ${alias}  ${org_id}  ${reference}  ${tenant}
  Set Tenant Context  ${tenant}
  VAR  ${API_ALIAS} =  ${alias}  scope=suite
  VAR  ${ORGANISATION_ID} =  ${org_id}  scope=suite
  ${external_id} =  Generate External ID
  ${dept_id} =  Get Department ID
  ${subj_id} =  Get Subject ID
  ${today} =  Get Current Date  result_format=%Y-%m-%d
  ${date_from} =  Subtract Time From Date  ${today}  14 days  result_format=%Y-%m-%d
  ${date_to} =  Subtract Time From Date  ${today}  7 days  result_format=%Y-%m-%d
  VAR  &{main_document} =
  ...  fileName=dummy.txt
  ...  type=c_4f50ca9c
  ...  formalDate=${date_from}
  ...  grounds=@{EMPTY}
  ...  language=NLD
  VAR  &{body} =
  ...  decision=public
  ...  departmentId=${dept_id}
  ...  dateFrom=${date_from}
  ...  dateTo=${date_to}
  ...  dossierNumber=${reference}
  ...  previewDate=${today}
  ...  publicationDate=${today}
  ...  reason=woo_request
  ...  subjectId=${subj_id}
  ...  summary=Isolation test dossier
  ...  title=${reference}
  ...  mainDocument=${main_document}
  ...  attachments=@{EMPTY}
  ...  documents=@{EMPTY}
  Send Put Request WooDecision  ${external_id}  ${body}  200  ${alias}
  Upload Main Document  woo-decision  ${TEST_DATA_ROOT}/dummy.txt  ${external_id}
  Wait Until Keyword Succeeds  10x  3s  Publication Status Should Be  woo-decision  published
