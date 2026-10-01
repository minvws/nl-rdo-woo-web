*** Settings ***
Documentation       Resource file containing custom keywords for the Subjects section in the Admin portal.
Library             String
Library             Browser
Library             DebugLibrary
Resource            ../resources/Generic.resource
Resource            ../resources/Public.resource


*** Variables ***
${CONTENT_TREE_LOCATOR}    //*[@data-e2e-name="content-tree"]


*** Keywords ***
Click Subjects
  Click  "Onderwerpen"

Create New Subject
  [Documentation]  If no params are provided, a random name is generated.
  [Arguments]  ${name}=EMPTY
  IF  '${name}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${name} =  E2E Test Subject ${random}
  END
  Click  //a[@data-e2e-name="new-subject"]
  Fill Text  id=subject_name  ${name}
  Click  id=subject_submit
  Success Alert Is Visible  Het onderwerp is aangemaakt
  RETURN  ${name}

Update Subject
  [Documentation]  If no params are provided, random numbers are added to the fields and checkbox is swapped.
  [Arguments]  ${name}  ${updated_name}=EMPTY
  Click  //tbody[@data-e2e-name="subject-list"]//a[contains(.,'${name}')]
  IF  '${updated_name}' == 'EMPTY'
    ${random} =  Generate Random String  1  [NUMBERS]
    ${updated_name} =  Get Text  id=subject_name
    ${updated_name} =  Catenate  ${updated_name}${random}
  END
  Fill Text  id=subject_name  ${updated_name}
  Click  id=subject_submit
  Success Alert Is Visible  Het onderwerp is aangepast

Ensure There Are More Than 10 Subjects
  Click  " Onderwerpen "
  ${nr_of_subjects} =  Get Element Count  //tbody[@data-e2e-name="subject-list"]//tr
  ${missing_subjects} =  Evaluate  max(10 - ${nr_of_subjects} , 0)
  FOR  ${_}  IN RANGE  1  ${missing_subjects}
    ${random_id} =  Generate Random String  5  [NUMBERS][UPPER]
    Create New Subject  Onderwerp ${random_id}
  END

Click Edit Subject Landingpage
  [Arguments]  ${name}
  Click  //tbody[@data-e2e-name="subject-list"]//tr[contains(.,'${name}')]/td[1]/a
  Click  //*[@data-e2e-name="update-landing-page"]

Subject List Landingpage Status Should Contain
  [Documentation]  Checks the landingpage column of the subject list, for the row of \${name}.
  [Arguments]  ${name}  ${expected}
  Get Text  //tbody[@data-e2e-name="subject-list"]//tr[contains(.,'${name}')]/td[3]  contains  ${expected}

Click Subject List Landingpage Edit Link
  [Documentation]  Clicks the "bewerken" link in the landingpage column of the subject list, for the row
  ...              of \${name}.
  [Arguments]  ${name}
  Click  //tbody[@data-e2e-name="subject-list"]//tr[contains(.,'${name}')]/td[3]/a

Subject List Landingpage Edit Link Should Not Exist
  [Documentation]  Checks that the "bewerken" link is absent from the landingpage column of the subject list,
  ...              for the row of \${name} - the case when it has no landingpage yet.
  [Arguments]  ${name}
  Get Element States  //tbody[@data-e2e-name="subject-list"]//tr[contains(.,'${name}')]/td[3]/a  contains  detached

Subject List Landingpage Edit Link Should Be Visible
  [Documentation]  Checks that the "bewerken" link is visible in the landingpage column of the subject list,
  ...              for the row of \${name}.
  [Arguments]  ${name}
  Get Element States  //tbody[@data-e2e-name="subject-list"]//tr[contains(.,'${name}')]/td[3]/a  contains  visible

Fill Subject Landingpage
  [Documentation]  If no slug/title/description are provided, random values are generated.
  [Arguments]
  ...  ${status}=published
  ...  ${slug}=EMPTY
  ...  ${title}=EMPTY
  ...  ${description}=EMPTY
  IF  '${slug}' == 'EMPTY'
    ${random} =  Generate Random String  8  [LOWER][NUMBERS]
    VAR  ${slug} =  e2e-subject-${random}
  END
  IF  '${title}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${title} =  A Random Subject Landingpage Title ${random}
  END
  IF  '${description}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${description} =  A random subject landingpage description ${random}.
  END
  Select Options By  id=subject_landing_page_landing_page_status  value  ${status}
  Fill Text  id=subject_landing_page_landing_page_slug  ${slug}
  Fill Text  id=subject_landing_page_landing_page_title  ${title}
  Fill Text  id=subject_landing_page_landing_page_description  ${description}
  RETURN  ${slug}  ${title}  ${description}

Set Subject Landingpage Content Tree Status
  [Arguments]  ${status}=published
  Select Options By  id=subject_landing_page_landing_page_content_tree_status  value  ${status}

Click Submit Subject Landingpage
  Click  //*[@data-e2e-name="submit-basic-details"]
  Success Alert Is Visible  Het onderwerp is aangepast

Verify Subject Landingpage Is Reachable From Home Page
  [Documentation]  The home page lists published subject landing pages by name, alphabetically, showing only
  ...              the first 10 - if this one isn't in that initial list, it follows the "view all" link
  ...              (present whenever there are more than 10) to the unlimited list instead of assuming it will
  ...              always fit in the visible window.
  [Arguments]  ${name}  ${slug}
  Go To Public
  Reload
  ${on_home_page} =  Run Keyword And Return Status
  ...  Get Text  //*[@id="onderwerppaginas"]  contains  ${name}
  IF  not ${on_home_page}
    Click  //*[@id="onderwerppaginas"]//a[contains(.,"Naar alle onderwerppagina's")]
    Get Text  //*[@id="onderwerppaginas"]  contains  ${name}
  END
  Click  //*[@id="onderwerppaginas"]//a[contains(.,'${name}')]
  Get Url  equal  ${URL_PUBLIC}/onderwerp/${slug}

Content Tree Node Locator
  [Arguments]  ${number}
  RETURN  ${CONTENT_TREE_LOCATOR}//*[@data-e2e-name="content-tree-node-${number}"]

Fill Content Tree Title
  [Arguments]  ${title}
  Fill Text  ${CONTENT_TREE_LOCATOR}//*[@data-e2e-name="content-tree-title"]  ${title}

Fill Content Tree Intro
  [Arguments]  ${intro}
  Fill Text  ${CONTENT_TREE_LOCATOR}//*[@data-e2e-name="content-tree-intro"]//textarea  ${intro}

Fill Content Tree Outro
  [Arguments]  ${outro}
  Fill Text  ${CONTENT_TREE_LOCATOR}//*[@data-e2e-name="content-tree-outro"]//textarea  ${outro}

Add Content Tree Node
  [Documentation]  Clicks "Onderwerp N toevoegen" to add a node. With no \${parent_number}, adds a root-level
  ...              node; otherwise adds a child under the node numbered \${parent_number} (e.g. "1"). Returns the
  ...              new node's own number (e.g. "2" or "1.1").
  [Arguments]  ${parent_number}=EMPTY
  IF  '${parent_number}' == 'EMPTY'
    ${existing} =  Get Element Count  ${CONTENT_TREE_LOCATOR}/div[@data-level]
    ${number} =  Evaluate  ${existing} + 1
    Click  ${CONTENT_TREE_LOCATOR}//*[@data-e2e-name="content-tree-add-node"]
  ELSE
    ${parent} =  Content Tree Node Locator  ${parent_number}
    ${existing} =  Get Element Count  ${parent}/div[@data-level]
    ${child} =  Evaluate  ${existing} + 1
    VAR  ${number} =  ${parent_number}.${child}
    Click  ${parent}/div/button[@data-e2e-name="content-tree-node-add-child"]
  END
  RETURN  ${number}

Fill Content Tree Node
  [Documentation]  A node's title/body wrapper names are reused by every nested child node, so the match is
  ...              pinned to the first one in document order - the node's own, since it renders before its children.
  [Arguments]  ${number}  ${title}  ${body}
  ${node} =  Content Tree Node Locator  ${number}
  Fill Text  (${node}//*[@data-e2e-name="content-tree-node-title"]//input)[1]  ${title}
  Fill Text  (${node}//*[@data-e2e-name="content-tree-node-body"]//textarea)[1]  ${body}

Remove Content Tree Node
  [Documentation]  Pinned to the first match in document order for the same reason as "Fill Content Tree Node".
  [Arguments]  ${number}
  ${node} =  Content Tree Node Locator  ${number}
  Click  (${node}//*[@data-e2e-name="content-tree-node-remove"])[1]
