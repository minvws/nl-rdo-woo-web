*** Settings ***
Documentation       Checks if the public declaration of accessibility is still downloadable.
Resource            ../resources/Setup.resource
Resource            ../resources/Public.resource
Suite Setup         Suite Setup
Test Tags           ci  accessibility


*** Test Cases ***
Check Declaration Of Accessibility
  [Documentation]  Checks if the external accessibility page for open.minvws.nl is working, as well as the downloadable PDF report.
  [Tags]  test:retry(2)
  Go To Public
  Click  'Toegankelijkheid'
  Click  //main//span[1]/a
  Get Text  //header  contains  Toegankelijkheidsverklaring
  Go Back
  ${url} =  Get Attribute  //main//span[2]/a  href
  Generic Download URL  ${url}


*** Keywords ***
Suite Setup
  Suite Setup Generic
