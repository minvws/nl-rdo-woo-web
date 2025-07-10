*** Settings ***
Documentation       Tests that focus on testing the content pages
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Library             FakerLibrary
Suite Setup         Suite Setup
Test Setup          Go To Admin
Test Tags           ci  contentpages


*** Test Cases ***
Edit ContentPage
  Go To ContentPage Admin
  ${title} =  FakerLibrary.Sentence
  Update Content Page  over-dit-platform  ${title}  **Ok gaan we dan**\n\n_Hoppa!_\n\n- Hatsee\n- Bam!
  Go To  %{URL_PUBLIC}/over-dit-platform
  Verify Content Page
  ...  ${title}
  ...  <p><span class="font-bold">Ok gaan we dan</span></p>\n<p><span class="italic">Hoppa!</span></p>\n<ul>\n<li>Hatsee</li>\n<li>Bam!</li>


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Go To ContentPage Admin
  Go To  %{URL_ADMIN}/admin/content-pages

Update Content Page
  [Arguments]  ${slug}  ${title}  ${content}
  Click  //*[@data-e2e-name="content-page-edit"][contains(@href,'${slug}')]
  Fill Text  //*[@id="content_page_title"]  ${title}
  Fill Text  //*[@id="content_page_content"]  ${content}
  Click  //*[@id="content_page_submit"]
  Success Alert Is Visible  De content is aangepast

Verify Content Page
  [Arguments]  ${title}  ${content}
  Get Text  //*[@data-e2e-name="content-page-body"]/h1  equals  ${title}
  Get Property  //*[@data-e2e-name="content-page-body"]/div  innerHTML  contains  ${content}
