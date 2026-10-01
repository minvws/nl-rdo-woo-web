*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Tests for the Subjects API
Library             Collections
Library             RequestsLibrary
Resource            ../../resources/API.resource
Suite Setup         Suite Setup API
Test Tags           api  subject


*** Variables ***
${ORGANISATION_ID}        ${EMPTY}
${CREATED_SUBJECT}        ${EMPTY}
${UPDATED_SUBJECT}        ${EMPTY}
${NEW_NAME}               ${EMPTY}
${IN_USE_SUBJECT}         ${EMPTY}
${LANDING_PAGE_SUBJECT}   ${EMPTY}
${SENT_LANDING_PAGE}      ${EMPTY}
${VALIDATION_RESPONSE}    ${EMPTY}


*** Test Cases ***
Get All Subjects
  [Documentation]  Reads all subjects and checks if the E2E Test Subject, from the fixtures, is present.
  ${url} =  Subject Collection Url
  ${response} =  GET On Session  alias=${API_ALIAS}  url=${url}  msg=GET request failed
  Get Object From Json By Attribute  ${response.json()}  name  E2E Test Subject
  Status Should Be  200  ${response}  msg=GET request did not return a 200

Create A Subject
  When A Subject Is Created
  Then We Can Find It

Update A Subject
  When The Subject Is Updated
  Then The Subject Has The New Values

Delete An Unused Subject
  When The Subject Is Deleted
  Then We Cannot Find It

Delete A Used Subject
  When A Subject Is Created And Linked To A Dossier
  Then We Cannot Delete It

Create A Subject With A Landing Page And Content Tree
  When A Subject Is Created With A Landing Page
  Then The Landing Page Is Returned As Sent
  Then We Can Find The Same Landing Page

Publishing A Subject Landing Page Clears Its Preview URL
  Given A Subject Is Created With A Landing Page
  Then The Concept Landing Page Has A Preview URL
  When The Landing Page Status Is Changed To Published
  Then The Published Landing Page Has No Preview URL

Content Tree Nesting Beyond Three Levels Is Rejected
  When A Subject Landing Page Is Created With A Four Level Deep Content Tree
  Then The Response Is A Content Tree Depth Validation Error


*** Keywords ***
Subject Collection Url
  RETURN  ${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject

Subject Url
  [Arguments]  ${id}
  RETURN  ${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/subject/${id}

Create Subject
  [Documentation]  POSTs a new subject (a random name is generated unless one is given), optionally with a
  ...              landing page, and returns the parsed JSON response.
  [Arguments]  ${name}=EMPTY  ${landing_page}=${EMPTY}
  IF  '${name}' == 'EMPTY'
    ${id} =  FakerLibrary.Md 5
    VAR  ${name} =  Random Subject - ${id}
  END
  VAR  &{body} =  name=${name}
  # Not a string comparison ('${landing_page}' != '${EMPTY}') on purpose: landing_page is a dict, and its
  # Python repr contains quotes that break a condition built by wrapping it in quoted string literals.
  IF  $landing_page  Set To Dictionary  ${body}  landingPage  ${landing_page}
  ${url} =  Subject Collection Url
  ${response} =  POST On Session
  ...  alias=${API_ALIAS}
  ...  url=${url}
  ...  json=${body}
  ...  expected_status=201
  ...  msg=POST request failed
  RETURN  ${response.json()}

Default Content Tree
  [Documentation]  A small but non-trivial tree: two root nodes, the first with a nested child.
  VAR  &{grandchild} =  title=Detail  body=Detail van het eerste onderwerp.  children=@{EMPTY}
  VAR  @{grandchildren} =  ${grandchild}
  VAR  &{child_one} =
  ...  title=Eerste onderwerp  body=Beschrijving van het eerste onderwerp.  children=${grandchildren}
  VAR  &{child_two} =
  ...  title=Tweede onderwerp  body=Beschrijving van het tweede onderwerp.  children=@{EMPTY}
  VAR  @{children} =  ${child_one}  ${child_two}
  VAR  &{tree} =
  ...  title=Titel van de verhaallijn
  ...  intro=Achtergrond bij de verhaallijn.
  ...  outro=Samenvatting van de verhaallijn.
  ...  children=${children}
  RETURN  ${tree}

Four Level Deep Content Tree
  [Documentation]  A single chain nested one level past the allowed maximum (3), for the rejection test.
  VAR  &{level4} =  title=Level 4  body=Body.  children=@{EMPTY}
  VAR  @{level4_list} =  ${level4}
  VAR  &{level3} =  title=Level 3  body=Body.  children=${level4_list}
  VAR  @{level3_list} =  ${level3}
  VAR  &{level2} =  title=Level 2  body=Body.  children=${level3_list}
  VAR  @{level2_list} =  ${level2}
  VAR  &{level1} =  title=Level 1  body=Body.  children=${level2_list}
  VAR  @{level1_list} =  ${level1}
  VAR  &{tree} =
  ...  title=Storyline title
  ...  intro=Storyline intro.
  ...  outro=Storyline outro.
  ...  children=${level1_list}
  RETURN  ${tree}

Default Landing Page
  [Documentation]  If no slug is given, a random one is generated so runs don't collide on the unique slug
  ...              constraint.
  [Arguments]  ${slug}=EMPTY  ${status}=concept
  IF  '${slug}' == 'EMPTY'
    ${id} =  FakerLibrary.Md 5
    VAR  ${slug} =  e2e-subject-${id}
  END
  ${content_tree} =  Default Content Tree
  VAR  &{landing_page} =
  ...  slug=${slug}
  ...  status=${status}
  ...  title=Titel van de landingspagina
  ...  description=Introductietekst als platte tekst.
  ...  contentTreeStatus=published
  ...  contentTree=${content_tree}
  RETURN  ${landing_page}

A Subject Is Created
  ${created} =  Create Subject
  VAR  ${CREATED_SUBJECT} =  ${created}  scope=suite

We Can Find It
  ${url} =  Subject Url  ${CREATED_SUBJECT}[id]
  GET On Session  alias=${API_ALIAS}  url=${url}  expected_status=200

The Subject Is Updated
  ${id_updated} =  FakerLibrary.Md 5
  VAR  ${NEW_NAME} =  Random Subject ${id_updated}  scope=suite
  VAR  &{body} =  name=${NEW_NAME}
  ${url} =  Subject Url  ${CREATED_SUBJECT}[id]
  ${put_response} =  PUT On Session
  ...  alias=${API_ALIAS}
  ...  url=${url}
  ...  json=${body}
  ...  expected_status=200
  ...  msg=PUT request failed
  VAR  ${UPDATED_SUBJECT} =  ${put_response.json()}  scope=suite
  Should Be Equal As Strings  ${put_response.json()}[name]  ${NEW_NAME}

The Subject Has The New Values
  Should Be Equal As Strings  ${UPDATED_SUBJECT}[name]  ${NEW_NAME}

The Subject Is Deleted
  ${url} =  Subject Url  ${CREATED_SUBJECT}[id]
  DELETE On Session  alias=${API_ALIAS}  url=${url}  expected_status=204  msg=DELETE request failed

We Cannot Find It
  ${url} =  Subject Url  ${CREATED_SUBJECT}[id]
  GET On Session  alias=${API_ALIAS}  url=${url}  expected_status=404

A Subject Is Created And Linked To A Dossier
  ${id} =  FakerLibrary.Word
  ${created} =  Create Subject  name=Random Subject - ${id}
  VAR  ${IN_USE_SUBJECT} =  ${created}  scope=suite
  ${dossier_ext_id} =  FakerLibrary.Md 5
  ${today} =  Get Current Date  result_format=%Y-%m-%d
  ${grounds} =  Get Random Grounds
  VAR  &{main_doc} =
  ...  fileName=dummy.txt
  ...  formalDate=${today}
  ...  grounds=${grounds}
  ...  language=NLD
  ...  type=c_3d782f30
  VAR  @{attachments} =  @{EMPTY}
  ${department_id} =  Get Department ID
  VAR  &{body} =
  ...  departmentId=${department_id}
  ...  dossierNumber=robot-api-${dossier_ext_id}
  ...  publicationDate=${today}
  ...  subjectId=${IN_USE_SUBJECT}[id]
  ...  summary=Robot subject in use test
  ...  title=Robot Subject In Use ${dossier_ext_id}
  ...  year=${2025}
  ...  mainDocument=${main_doc}
  ...  attachments=${attachments}
  PUT On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/annual-report/external/${dossier_ext_id}
  ...  json=${body}
  ...  expected_status=200
  ...  msg=Create annual-report dossier failed

We Cannot Delete It
  ${url} =  Subject Url  ${IN_USE_SUBJECT}[id]
  DELETE On Session
  ...  alias=${API_ALIAS}
  ...  url=${url}
  ...  expected_status=405
  ...  msg=DELETE of used subject should return 405

A Subject Is Created With A Landing Page
  ${landing_page} =  Default Landing Page
  ${created} =  Create Subject  landing_page=${landing_page}
  VAR  ${LANDING_PAGE_SUBJECT} =  ${created}  scope=suite
  VAR  ${SENT_LANDING_PAGE} =  ${landing_page}  scope=suite

The Landing Page Is Returned As Sent
  Should Be Equal As Strings  ${LANDING_PAGE_SUBJECT}[landingPage][slug]  ${SENT_LANDING_PAGE}[slug]
  Should Be Equal As Strings  ${LANDING_PAGE_SUBJECT}[landingPage][status]  ${SENT_LANDING_PAGE}[status]
  Should Be Equal As Strings  ${LANDING_PAGE_SUBJECT}[landingPage][title]  ${SENT_LANDING_PAGE}[title]
  Should Be Equal As Strings  ${LANDING_PAGE_SUBJECT}[landingPage][description]  ${SENT_LANDING_PAGE}[description]
  Should Be Equal
  ...  ${LANDING_PAGE_SUBJECT}[landingPage][contentTreeStatus]
  ...  ${SENT_LANDING_PAGE}[contentTreeStatus]
  Should Be Equal  ${LANDING_PAGE_SUBJECT}[landingPage][contentTree]  ${SENT_LANDING_PAGE}[contentTree]

We Can Find The Same Landing Page
  ${url} =  Subject Url  ${LANDING_PAGE_SUBJECT}[id]
  ${response} =  GET On Session  alias=${API_ALIAS}  url=${url}  expected_status=200
  Should Be Equal  ${response.json()}[landingPage]  ${LANDING_PAGE_SUBJECT}[landingPage]

The Concept Landing Page Has A Preview URL
  Should Not Be Equal  ${LANDING_PAGE_SUBJECT}[landingPage][previewUrl]  ${NONE}

The Landing Page Status Is Changed To Published
  ${published} =  Copy Dictionary  ${SENT_LANDING_PAGE}  deepcopy=True
  Set To Dictionary  ${published}  status  published
  VAR  &{body} =  name=${LANDING_PAGE_SUBJECT}[name]  landingPage=${published}
  ${url} =  Subject Url  ${LANDING_PAGE_SUBJECT}[id]
  ${put_response} =  PUT On Session
  ...  alias=${API_ALIAS}
  ...  url=${url}
  ...  json=${body}
  ...  expected_status=200
  ...  msg=PUT request failed
  VAR  ${LANDING_PAGE_SUBJECT} =  ${put_response.json()}  scope=suite

The Published Landing Page Has No Preview URL
  Should Be Equal  ${LANDING_PAGE_SUBJECT}[landingPage][previewUrl]  ${NONE}

A Subject Landing Page Is Created With A Four Level Deep Content Tree
  ${content_tree} =  Four Level Deep Content Tree
  ${landing_page} =  Default Landing Page
  Set To Dictionary  ${landing_page}  contentTree  ${content_tree}
  VAR  &{body} =  name=Subject with too-deep content tree  landingPage=${landing_page}
  ${url} =  Subject Collection Url
  ${response} =  POST On Session
  ...  alias=${API_ALIAS}
  ...  url=${url}
  ...  json=${body}
  ...  expected_status=422
  ...  msg=POST with a too-deep content tree should be rejected
  VAR  ${VALIDATION_RESPONSE} =  ${response}  scope=suite

The Response Is A Content Tree Depth Validation Error
  VAR  ${violations} =  ${VALIDATION_RESPONSE.json()}[violations]
  Should Be Equal As Strings
  ...  ${violations}[0][propertyPath]
  ...  landingPage.contentTree.children[0].children[0].children[0].children
  Should Be Equal As Strings  ${violations}[0][message]  The maximum depth of the content tree has been exceeded
