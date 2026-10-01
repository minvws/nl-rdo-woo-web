*** Settings ***
Documentation       Tests that create DraftDecision dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/DraftDecision.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment
Ontwerpbesluit, Concept                               draft-decision        Concept               ${FALSE}
Ontwerpbesluit, Gepubliceerd                          draft-decision        Gepubliceerd          ${FALSE}
Ontwerpbesluit, Gepubliceerd, Bijlage                 draft-decision        Gepubliceerd          ${TRUE}
Ontwerpbesluit, Gepland                               draft-decision        Gepland               ${FALSE}


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
  [Arguments]  ${type}  ${publication_status}  ${has_attachment}
  Create New Dossier  ${type}
  Generate Test Data Set  ${type}  ${has_attachment}
  Fill Out Basic Details  type=${type}
  Fill Out DraftDecision Details  ${has_attachment}
  Publish Dossier And Return To Admin Home  ${publication_status}
