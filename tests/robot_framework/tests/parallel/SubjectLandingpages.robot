*** Settings ***
Documentation       Tests that focus on creating subjects and publishing their landingpage on public, including
...                 its content tree ("Verhaallijn") editor.
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Close Browser
Test Tags           ci  subject-landingpages


*** Test Cases ***
Create A New Subject
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Get Text  //tbody[@data-e2e-name="subject-list"]  contains  ${name}
  # Without a landingpage there is nothing to edit, so no link in the list.
  Subject List Landingpage Status Should Contain  ${name}  Nee
  Subject List Landingpage Edit Link Should Not Exist  ${name}

Publish A Subject Landingpage
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${title}  ${description} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Subject List Landingpage Status Should Contain  ${name}  Ja
  Subject List Landingpage Edit Link Should Be Visible  ${name}
  # The public subject pages section only renders once at least 2 are published, so publish
  # a second, throwaway one here to make this one's discoverable from the home page.
  ${other_name} =  Create New Subject
  Click Edit Subject Landingpage  ${other_name}
  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/h1  contains  ${title}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]  contains  ${description}
  # No content tree was ever filled in, so it should never render on the public page.
  Get Element States  //ul[contains(@class,'woo-accordion-list')]  contains  detached
  Verify Subject Landingpage Is Reachable From Home Page  ${name}  ${slug}

Concept Subject Landingpage Is Not Publicly Visible
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=concept
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Verify Page Error  404
  # A concept with content is shown as such in the subject list, with a link to keep editing it.
  Login Admin
  Click Subjects
  Subject List Landingpage Status Should Contain  ${name}  Concept
  Click Subject List Landingpage Edit Link  ${name}
  Get Property  id=subject_landing_page_landing_page_slug  value  equal  ${slug}

Update An Existing Subject Landingpage
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  ${_}  ${updated_title}  ${updated_description} =  Fill Subject Landingpage  status=published  slug=${slug}
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/h1  contains  ${updated_title}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]  contains  ${updated_description}

Content Tree Renders When Visible And Populated
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Fill Content Tree Title  Een titel voor de verhaallijn
  Fill Content Tree Intro  Achtergrondinformatie bij de verhaallijn.
  Fill Content Tree Outro  Afsluitende samenvatting van de verhaallijn.
  Add Content Tree Node
  Fill Content Tree Node  1  Eerste onderwerp  Beschrijving van het eerste onderwerp.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*/h2  contains  Een titel voor de verhaallijn
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*  contains  Achtergrondinformatie bij de verhaallijn.
  Get Text  //ul[contains(@class,'woo-accordion-list')]//summary  contains  Eerste onderwerp
  Get Text  //ul[contains(@class,'woo-accordion-list')]  contains  Beschrijving van het eerste onderwerp.
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*  contains  Afsluitende samenvatting van de verhaallijn.

Content Tree Stays Hidden Until Visibility Is Enabled
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Fill Content Tree Title  Nog een titel voor de verhaallijn
  Fill Content Tree Intro  Deze introductie hoort niet zichtbaar te zijn.
  Fill Content Tree Outro  Deze samenvatting hoort niet zichtbaar te zijn.
  Add Content Tree Node
  Fill Content Tree Node  1  Onzichtbaar onderwerp  Deze tekst hoort niet zichtbaar te zijn.
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  VAR  ${body_parent} =  //*[@data-e2e-name="subject-landing-page-body"]/parent::*
  Get Text  ${body_parent}  not contains  Nog een titel voor de verhaallijn
  Get Text  ${body_parent}  not contains  Deze introductie hoort niet zichtbaar te zijn.
  Get Text  ${body_parent}  not contains  Deze samenvatting hoort niet zichtbaar te zijn.
  Get Element States  //ul[contains(@class,'woo-accordion-list')]  contains  detached

Content Tree Supports Nested Nodes
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Fill Content Tree Title  Titel van de geneste verhaallijn
  Fill Content Tree Intro  Achtergrond bij de geneste verhaallijn.
  Fill Content Tree Outro  Samenvatting van de geneste verhaallijn.
  # Three root nodes.
  Add Content Tree Node
  Fill Content Tree Node  1  Hoofdonderwerp 1  Beschrijving van hoofdonderwerp 1.
  Add Content Tree Node
  Fill Content Tree Node  2  Hoofdonderwerp 2  Beschrijving van hoofdonderwerp 2.
  Add Content Tree Node
  Fill Content Tree Node  3  Hoofdonderwerp 3  Beschrijving van hoofdonderwerp 3.
  # Three children under the first root node.
  Add Content Tree Node  1
  Fill Content Tree Node  1.1  Subonderwerp 1  Beschrijving van subonderwerp 1.
  Add Content Tree Node  1
  Fill Content Tree Node  1.2  Subonderwerp 2  Beschrijving van subonderwerp 2.
  Add Content Tree Node  1
  Fill Content Tree Node  1.3  Subonderwerp 3  Beschrijving van subonderwerp 3.
  # Three children under the first of those.
  Add Content Tree Node  1.1
  Fill Content Tree Node  1.1.1  Subsubonderwerp 1  Beschrijving van subsubonderwerp 1.
  Add Content Tree Node  1.1
  Fill Content Tree Node  1.1.2  Subsubonderwerp 2  Beschrijving van subsubonderwerp 2.
  Add Content Tree Node  1.1
  Fill Content Tree Node  1.1.3  Subsubonderwerp 3  Beschrijving van subsubonderwerp 3.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Get Text
  ...  //*[@data-e2e-name="subject-landing-page-body"]/parent::*/h2
  ...  contains  Titel van de geneste verhaallijn
  Get Text
  ...  //*[@data-e2e-name="subject-landing-page-body"]/parent::*
  ...  contains  Achtergrond bij de geneste verhaallijn.
  # Each level's own children list carries a data-e2e-name keyed to its node number, so it can be targeted
  # directly instead of matching individual <summary> elements (ambiguous with three siblings) or descending
  # relative to the previous level (which would also match a node's own grandchildren list nested further down).
  Get Text  //*[@data-e2e-name="content-tree-node-1"]  contains  Hoofdonderwerp 1
  Get Text  //*[@data-e2e-name="content-tree-node-2"]  contains  Hoofdonderwerp 2
  Get Text  //*[@data-e2e-name="content-tree-node-3"]  contains  Hoofdonderwerp 3
  VAR  ${level2} =  //*[@data-e2e-name="content-tree-node-1-children"]
  Get Text  ${level2}  contains  Subonderwerp 1
  Get Text  ${level2}  contains  Subonderwerp 2
  Get Text  ${level2}  contains  Subonderwerp 3
  VAR  ${level3} =  //*[@data-e2e-name="content-tree-node-1.1-children"]
  Get Text  ${level3}  contains  Subsubonderwerp 1
  Get Text  ${level3}  contains  Subsubonderwerp 2
  Get Text  ${level3}  contains  Subsubonderwerp 3
  Get Text  ${level3}  contains  Beschrijving van subsubonderwerp 1.
  Get Text
  ...  //*[@data-e2e-name="subject-landing-page-body"]/parent::*
  ...  contains  Samenvatting van de geneste verhaallijn.

Content Tree Rejects Nesting Beyond Three Levels
  [Documentation]  SubjectContentTree caps nesting at 3 levels; a 4th level
  ...              should be rejected with a validation error rather than saved.
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${_}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Niveau 1  Beschrijving van niveau 1.
  Add Content Tree Node  1
  Fill Content Tree Node  1.1  Niveau 2  Beschrijving van niveau 2.
  Add Content Tree Node  1.1
  Fill Content Tree Node  1.1.1  Niveau 3  Beschrijving van niveau 3.
  Add Content Tree Node  1.1.1
  Fill Content Tree Node  1.1.1.1  Niveau 4  Beschrijving van niveau 4.
  Click  //*[@data-e2e-name="submit-content-tree"]
  Input Errors Should Be Present

Removing A Content Tree Node Removes It From The Public Page
  Login Admin
  Click Subjects
  ${name} =  Create New Subject
  Click Edit Subject Landingpage  ${name}
  ${slug}  ${_}  ${_} =  Fill Subject Landingpage  status=published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Fill Content Tree Title  Titel die blijft staan
  Fill Content Tree Intro  Introductie die blijft staan.
  Fill Content Tree Outro  Samenvatting die blijft staan.
  Add Content Tree Node
  Fill Content Tree Node  1  Blijft staan  Deze tekst hoort te blijven staan.
  Add Content Tree Node
  Fill Content Tree Node  2  Wordt verwijderd  Deze tekst hoort te verdwijnen.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Remove Content Tree Node  2
  Click Submit Subject Landingpage
  Go To  ${URL_PUBLIC}/onderwerp/${slug}
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*/h2  contains  Titel die blijft staan
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*  contains  Introductie die blijft staan.
  Get Text  //ul[contains(@class,'woo-accordion-list')]  contains  Blijft staan
  Get Text  //ul[contains(@class,'woo-accordion-list')]  not contains  Wordt verwijderd
  Get Text  //*[@data-e2e-name="subject-landing-page-body"]/parent::*  contains  Samenvatting die blijft staan.


*** Keywords ***
Suite Setup
  Suite Setup Generic
