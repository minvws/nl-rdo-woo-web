*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Pagination tests for the Subjects API
Library             Collections
Library             RequestsLibrary
Resource            ../../resources/API.resource
Suite Setup         Suite Setup
Suite Teardown      Delete All Created Pagination Subjects
Test Tags           api  pagination


*** Variables ***
${ORGANISATION_ID}          ${EMPTY}
@{PAGINATION_CREATED_IDS}   ${EMPTY}
${PAGINATION_FIRST_PAGE}    ${EMPTY}


*** Test Cases ***
Pagination First Page Has 100 Items And A Next Link
  [Documentation]  Verifies that the first page returns exactly 100 items, hasNextPage is true,
  ...              and a HAL _links.next URL is present. Subject data is prepared in Suite Setup.
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  expected_status=200
  ...  msg=GET first page failed
  VAR  ${PAGINATION_FIRST_PAGE} =  ${response.json()}  scope=suite
  Length Should Be  ${PAGINATION_FIRST_PAGE}[items]  100
  Should Be True  ${PAGINATION_FIRST_PAGE}[hasNextPage]
  Dictionary Should Contain Key  ${PAGINATION_FIRST_PAGE}[_links]  next
  Dictionary Should Contain Key  ${PAGINATION_FIRST_PAGE}[_links][next]  href

Pagination Cursor Traversal Reaches All Created Subjects And Ends Cleanly
  [Documentation]  Follows cursor pages starting from page 2 until hasNextPage is false,
  ...              verifies the final page has no _links.next, and confirms every subject
  ...              created in Suite Setup appears across all pages. No exact total count is
  ...              asserted, making this stable when run in parallel with other suites.
  VAR  @{all_ids} =
  FOR  ${item}  IN  @{PAGINATION_FIRST_PAGE}[items]
    Append To List  ${all_ids}  ${item}[id]
  END
  VAR  ${url} =  ${PAGINATION_FIRST_PAGE}[_links][next][href]
  WHILE  True
    ${response} =  GET On Session
    ...  alias=publication_api
    ...  url=${url}
    ...  expected_status=200
    ...  msg=GET next page failed
    VAR  ${page} =  ${response.json()}
    Should Not Be Empty  ${page}[items]
    FOR  ${item}  IN  @{page}[items]
      Append To List  ${all_ids}  ${item}[id]
    END
    IF  not ${page}[hasNextPage]
      ${links} =  Evaluate  ${page}.get('_links')
      Should Be Equal  ${links}  ${None}
      BREAK
    END
    VAR  ${url} =  ${page}[_links][next][href]
  END
  FOR  ${created_id}  IN  @{PAGINATION_CREATED_IDS}
    Should Contain  ${all_ids}  ${created_id}
  END

Pagination Malformed Cursor Falls Back To First Page
  [Documentation]  Passes a syntactically invalid cursor. The implementation silently ignores
  ...              undecodable cursors and starts from the beginning, so the response should
  ...              look identical to a no-cursor request: 100 items, hasNextPage true.
  VAR  &{params} =  pagination[cursor]=not-a-valid-cursor!!!
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
  ...  params=${params}
  ...  expected_status=200
  ...  msg=GET with malformed cursor failed
  Length Should Be  ${response.json()}[items]  100
  Should Be True  ${response.json()}[hasNextPage]


*** Keywords ***
Suite Setup
  Suite Setup API
  Create Subjects For Pagination

Create Subjects For Pagination
  [Documentation]  Creates 110 subjects so the total exceeds the 100-item page size.
  ...              Stores all created IDs in suite-scoped PAGINATION_CREATED_IDS for cleanup and assertions.
  VAR  @{ids} =
  FOR  ${i}  IN RANGE  110
    ${rand} =  FakerLibrary.Numerify  ######
    VAR  ${name} =  Pg-${i}-${rand}
    VAR  &{body} =  name=${name}
    ${post_response} =  POST On Session
    ...  alias=publication_api
    ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject
    ...  json=${body}
    ...  expected_status=201
    ...  msg=POST pagination subject ${i} failed
    Append To List  ${ids}  ${post_response.json()}[id]
  END
  VAR  @{PAGINATION_CREATED_IDS} =  @{ids}  scope=suite

Delete All Created Pagination Subjects
  FOR  ${subject_id}  IN  @{PAGINATION_CREATED_IDS}
    DELETE On Session
    ...  alias=publication_api
    ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${subject_id}
    ...  expected_status=204
    ...  msg=DELETE pagination subject failed
  END
