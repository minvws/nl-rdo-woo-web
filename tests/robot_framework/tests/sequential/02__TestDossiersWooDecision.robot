*** Settings ***
Documentation       Tests that create WooDecision dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment    decision
Woo-besluit, Concept, Openbaarmaking                  woo-decision          Concept               ${FALSE}          Openbaarmaking
Woo-besluit, Concept, Geen Openbaarmaking             woo-decision          Concept               ${FALSE}          Geen openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking             woo-decision          Gepubliceerd          ${FALSE}          Openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking, Bijlage    woo-decision          Gepubliceerd          ${TRUE}           Openbaarmaking
Woo-besluit, Gepubliceerd, Geen Openbaarmaking        woo-decision          Gepubliceerd          ${FALSE}          Geen openbaarmaking
Woo-besluit, Gepland, Openbaarmaking                  woo-decision          Gepland               ${FALSE}          Openbaarmaking
Woo-besluit, Gepland, Geen Openbaarmaking             woo-decision          Gepland               ${FALSE}          Geen openbaarmaking


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation
  Ensure There Are More Than 10 Subjects
  Click Publications

Suite Teardown
  No-Click Logout
  Clear TestData Folder

Create Test Dossier
  [Arguments]  ${type}  ${publication_status}  ${has_attachment}  ${decision}=NotApplicable
  Create New Dossier  ${type}
  Generate Test Data Set  ${type}  ${has_attachment}
  Fill Out Basic Details  type=${type}
  Fill Out WooDecision Details  ${decision}  ${has_attachment}
  IF  "${decision}" == "Openbaarmaking"
    Upload Documents Step
  ELSE IF  "${decision}" == "Geen openbaarmaking"
    Click Save And Continue Production Report
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
