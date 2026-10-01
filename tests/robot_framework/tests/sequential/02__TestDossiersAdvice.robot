*** Settings ***
Documentation       Tests that create Advice and RequestForAdvice dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/Advice.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/RequestForAdvice.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Resource            ../../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment
Advies, Concept                                       advice                Concept               ${FALSE}
Advies, Gepubliceerd                                  advice                Gepubliceerd          ${FALSE}
Advies, Gepubliceerd, Bijlage                         advice                Gepubliceerd          ${TRUE}
Advies, Gepland                                       advice                Gepland               ${FALSE}
Adviesaanvraag, Concept                               request-for-advice    Concept               ${FALSE}
Adviesaanvraag, Gepubliceerd                          request-for-advice    Gepubliceerd          ${FALSE}
Adviesaanvraag, Gepubliceerd, Bijlage                 request-for-advice    Gepubliceerd          ${TRUE}
Adviesaanvraag, Gepland                               request-for-advice    Gepland               ${FALSE}


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
  IF  "${type}" == "advice"
    Fill Out Advice Details  ${has_attachment}
  ELSE IF  "${type}" == "request-for-advice"
    Fill Out Request For Advice Details  ${has_attachment}
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
