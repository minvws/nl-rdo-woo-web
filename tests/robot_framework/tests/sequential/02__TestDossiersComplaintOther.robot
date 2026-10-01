*** Settings ***
Documentation       Tests that create ComplaintJudgement and OtherPublication dossiers with a few variations.
...                 Must finish before 03__Public.robot runs (see order.txt).
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/ComplaintJudgement.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/OtherPublication.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Resource            ../../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init  test-acc


*** Test Cases ***                                    type                  publication_status    has_attachment
Klachtoordeel, Concept                                complaint-judgement   Concept               ${FALSE}
Klachtoordeel, Gepubliceerd                           complaint-judgement   Gepubliceerd          ${FALSE}
Klachtoordeel, Gepland                                complaint-judgement   Gepland               ${FALSE}
Overig, Concept                                       other-publication     Concept               ${FALSE}
Overig, Gepubliceerd                                  other-publication     Gepubliceerd          ${FALSE}
Overig, Gepubliceerd, Bijlage                         other-publication     Gepubliceerd          ${TRUE}
Overig, Gepland                                       other-publication     Gepland               ${FALSE}


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
  IF  "${type}" == "complaint-judgement"
    Fill Out Complaint Judgement Details
  ELSE IF  "${type}" == "other-publication"
    Fill Out Other Publication Details  ${has_attachment}
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
