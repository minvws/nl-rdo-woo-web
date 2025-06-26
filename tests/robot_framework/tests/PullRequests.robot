*** Settings ***
Documentation       Simple set to be run in PR's for a quick validation. Currently unused becasue we now use the whole WooDecision set in PRs.
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           single

*** Test Cases ***
Create a WooDecision
  Generate Test Data Set  woo-decision
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
