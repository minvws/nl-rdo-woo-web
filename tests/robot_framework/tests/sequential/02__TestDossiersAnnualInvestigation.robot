*** Settings ***
Documentation       Tests that create AnnualReport and InvestigationReport dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/AnnualReport.resource
Resource            ../../resources/InvestigationReport.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Resource            ../../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment
Jaarplan, Concept                                     annual-report         Concept               ${FALSE}
Jaarplan, Gepubliceerd                                annual-report         Gepubliceerd          ${FALSE}
Jaarplan, Gepubliceerd, Bijlage                       annual-report         Gepubliceerd          ${TRUE}
Jaarplan, Gepland                                     annual-report         Gepland               ${FALSE}
Onderzoeksrapport, Concept                            investigation-report  Concept               ${FALSE}
Onderzoeksrapport, Gepubliceerd                       investigation-report  Gepubliceerd          ${FALSE}
Onderzoeksrapport, Gepubliceerd, Bijlage              investigation-report  Gepubliceerd          ${TRUE}
Onderzoeksrapport, Gepland                            investigation-report  Gepland               ${FALSE}


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
  IF  "${type}" == "annual-report"
    Fill Out Annual Report Details  ${has_attachment}
  ELSE IF  "${type}" == "investigation-report"
    Fill Out Investigation Report Details  ${has_attachment}
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
