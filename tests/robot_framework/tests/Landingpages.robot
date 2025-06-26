*** Settings ***
Documentation       Tests that focus on the department landingpages on public
Resource            ../resources/Setup.resource
Resource            ../resources/Departments.resource
Suite Setup         Suite Setup
Test Setup          Go To Public
Test Tags           ci  landingpages

*** Test Cases ***
Enable landingpages
  Login Admin
  Click Departments
  Change Department Settings  E2E-DEP1  ${TRUE}
  Change Department Settings  E2E-DEP2  ${TRUE}
  Go To Public
  Click Bekijk Per Bestuursorgaan
  Get Text  //*[@data-e2e-name="departments"]  contains  E2E-DEP1
  Get Text  //*[@data-e2e-name="departments"]  contains  E2E-DEP2

Existing department landingpage works
  Click Bekijk Per Bestuursorgaan
  Navigate To Individual Landingpage  E2E-DEP1  e2edep1
  Navigate To Individual Landingpage  E2E-DEP2  e2edep2

Non-existing department landingpage results in 404
  Go To  localhost:8000/huppeldepup
  Verify Page Error  404

Invisble landingpage is not accessible
  Go To Admin
  # Make landingpage invisible
  Click Departments
  Change Department Settings  E2E-DEP1  ${FALSE}
  # Check through listing on home
  Go To Public
  Click Bekijk Per Bestuursorgaan
  Reload
  Get Text  //*[@data-e2e-name="departments"]  not contains  E2E-DEP1
  # Check through URL
  Go To  localhost:8000/e2edep1
  Reload
  Verify Page Error  404

Create Custom Landingpage
  Login Admin
  Click Departments
  Change Department Settings  E2E-DEP3  ${TRUE}
  Click Edit Department Landingpage  E2E-DEP3
  Fill Text  //*[@id="landing_page_landingpage_title"]  Ministerie van OhCeeWee
  Fill Text
  ...  //*[@id="landing_page_landingpage_description"]
  ...  **OhCeeWee**\n\n_Test_\n\n- item\n- item\n\n1. eerste\n2. tweede
  Click Submit Landingpage
  Success Alert Is Visible  De landingspagina is aangepast.
  Click Edit Department Landingpage  E2E-DEP3
  Click Logo Tab
  Upload Logo  tests/robot_framework/files/cheese.svg
  Verify Image  //*[@id="tabs-landingspagina-content-2"]//img  100
  Click Departments
  Click Department Public URL  E2E-DEP3
  Reload
  Verify Image  //*[@data-e2e-name="landing-page-img"]  100
  Get Text  //*[@data-e2e-name="landing-page-body"]/h1  contains  Ministerie van OhCeeWee
  Get Property
  ...  //*[@data-e2e-name="landing-page-body"]/div
  ...  innerHTML
  ...  contains
  ...  <p></p><p><span class="font-bold">OhCeeWee</span></p>\n<p><span class="italic">Test</span></p>\n<ul>\n<li>item</li>\n<li>item</li>\n</ul>\n<ol>\n<li>eerste</li>\n<li>tweede</li>\n</ol>

Remove Logo From Custom Landingpage
  Go To Admin
  Click Departments
  Click Edit Department Landingpage  E2E-DEP3
  Click Logo Tab
  Remove Logo
  Click Departments
  Click Department Public URL  E2E-DEP3
  Reload
  Get Element States  //*[@data-e2e-name="landing-page-img"]  contains  detached


*** Keywords ***
Suite Setup
  Suite Setup Generic

Navigate To Individual Landingpage
  [Arguments]  ${keyword}  ${slug}
  Click  "${keyword}"
  Get Url  equal  http://localhost:8000/${slug}
  Go Back
