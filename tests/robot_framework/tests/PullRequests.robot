*** Settings ***
Documentation       Simple set to be run in PR's for a quick validation. Currently unused becasue we now use the whole WooDecision set in PRs.
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           single


*** Test Cases ***
Create a WooDecision
  Publish Generated Test WooDecision


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
