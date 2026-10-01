*** Settings ***
Documentation       Tests that create Covenant and Disposition dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/Covenant.resource
Resource            ../../resources/Disposition.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment
Convenant, Concept                                    covenant              Concept               ${FALSE}
Convenant, Gepubliceerd                               covenant              Gepubliceerd          ${FALSE}
Convenant, Gepubliceerd, Bijlage                      covenant              Gepubliceerd          ${TRUE}
Convenant, Gepland                                    covenant              Gepland               ${FALSE}
Beschikking, Concept                                  disposition           Concept               ${FALSE}
Beschikking, Gepubliceerd                             disposition           Gepubliceerd          ${FALSE}
Beschikking, Gepubliceerd, Bijlage                    disposition           Gepubliceerd          ${TRUE}
Beschikking, Gepland                                  disposition           Gepland               ${FALSE}


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
  IF  "${type}" == "covenant"
    Fill Out Covenant Details  ${has_attachment}
  ELSE IF  "${type}" == "disposition"
    Fill Out Disposition Details  ${has_attachment}
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
