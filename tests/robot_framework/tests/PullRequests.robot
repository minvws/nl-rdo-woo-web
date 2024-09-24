*** Settings ***
Documentation       Simple set to be run in PR's for a quick validation.
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           pr


*** Test Cases ***
Create a WooDecision
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport4.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten4.zip
  ...  number_of_documents=3
  ...  publication_status=Openbaarmaking


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin
  Select Organisation

Suite Teardown
  Go To Admin
  Logout Admin
