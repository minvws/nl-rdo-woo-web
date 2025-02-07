*** Settings ***
Documentation       Tests that focus on the department landingpages on public
Resource            ../resources/Setup.resource
Resource            ../resources/Public.resource
Resource            ../resources/Generic.resource
Resource            ../resources/Departments.resource
Suite Setup         Suite Setup
Test Setup          Go To Public
Test Tags           landingpages  ci


*** Variables ***
${BASE_URL}     localhost:8000


*** Test Cases ***
Enable landingpages
  Login Admin
  Click Departments
  Change Department Settings  AZ  ${TRUE}
  Change Department Settings  BZK  ${TRUE}
  Change Department Settings  VWS  ${TRUE}
  Go To Public
  Sleep  1s
  Reload
  Click Bekijk Per Bestuursorgaan
  Get Text  //*[@data-e2e-name="departments"]  contains  AZ
  Get Text  //*[@data-e2e-name="departments"]  contains  BZK
  Get Text  //*[@data-e2e-name="departments"]  contains  VWS

Existing department landingpage works
  Click Bekijk Per Bestuursorgaan
  Navigate To Individual Landingpage  AZ
  Navigate To Individual Landingpage  BZK
  Navigate To Individual Landingpage  VWS

Non-existing department landingpage results in 404
  Go To  localhost:8000/huppeldepup
  Verify Page Error  404

Invisble landingpage is not accessible
  Go To Admin
  # Make landingpage invisible
  Click Departments
  Change Department Settings  AZ  ${FALSE}
  # Check through listing on home
  Go To Public
  Click Bekijk Per Bestuursorgaan
  Reload
  Get Text  //*[@data-e2e-name="departments"]  not contains  AZ
  # Check through URL
  Go To  localhost:8000/az
  Reload
  Verify Page Error  404


*** Keywords ***
Suite Setup
  Suite Setup - CI

Navigate To Individual Landingpage
  [Arguments]  ${keyword}
  Click  "${keyword}"
  ${lower} =  Convert To Lower Case  ${keyword}
  Get Url  equal  http://localhost:8000/${lower}
  Go Back
