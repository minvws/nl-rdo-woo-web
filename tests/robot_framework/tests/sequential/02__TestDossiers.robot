*** Settings ***
Documentation       Tests that create all types of dossiers with a few variations.
...                 This is named 02 because we want to run this before we run the 03 Public suite, so we have content to search for.
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../../resources/Advice.resource
Resource            ../../resources/AnnualReport.resource
Resource            ../../resources/ComplaintJudgement.resource
Resource            ../../resources/Covenant.resource
Resource            ../../resources/Disposition.resource
Resource            ../../resources/InvestigationReport.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/OtherPublication.resource
Resource            ../../resources/RequestForAdvice.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  sitemap-init


*** Test Cases ***                                    type                  publication_status    has_attachment    decision
Woo-besluit, Concept, Openbaarmaking                  woo-decision          Concept               ${FALSE}          Openbaarmaking
Woo-besluit, Concept, Geen Openbaarmaking             woo-decision          Concept               ${FALSE}          Geen openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking             woo-decision          Gepubliceerd          ${FALSE}          Openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking, Bijlage    woo-decision          Gepubliceerd          ${TRUE}           Openbaarmaking
Woo-besluit, Gepubliceerd, Geen Openbaarmaking        woo-decision          Gepubliceerd          ${FALSE}          Geen openbaarmaking
Woo-besluit, Gepland, Openbaarmaking                  woo-decision          Gepland               ${FALSE}          Openbaarmaking
Woo-besluit, Gepland, Geen Openbaarmaking             woo-decision          Gepland               ${FALSE}          Geen openbaarmaking
Convenant, Concept                                    covenant              Concept               ${FALSE}
Convenant, Gepubliceerd                               covenant              Gepubliceerd          ${FALSE}
Convenant, Gepubliceerd, Bijlage                      covenant              Gepubliceerd          ${TRUE}
Convenant, Gepland                                    covenant              Gepland               ${FALSE}
Beschikking, Concept                                  disposition           Concept               ${FALSE}
Beschikking, Gepubliceerd                             disposition           Gepubliceerd          ${FALSE}
Beschikking, Gepubliceerd, Bijlage                    disposition           Gepubliceerd          ${TRUE}
Beschikking, Gepland                                  disposition           Gepland               ${FALSE}
Jaarplan, Concept                                     annual-report         Concept               ${FALSE}
Jaarplan, Gepubliceerd                                annual-report         Gepubliceerd          ${FALSE}
Jaarplan, Gepubliceerd, Bijlage                       annual-report         Gepubliceerd          ${TRUE}
Jaarplan, Gepland                                     annual-report         Gepland               ${FALSE}
Onderzoeksrapport, Concept                            investigation-report  Concept               ${FALSE}
Onderzoeksrapport, Gepubliceerd                       investigation-report  Gepubliceerd          ${FALSE}
Onderzoeksrapport, Gepubliceerd, Bijlage              investigation-report  Gepubliceerd          ${TRUE}
Onderzoeksrapport, Gepland                            investigation-report  Gepland               ${FALSE}
Klachtoordeel, Concept                                complaint-judgement   Concept               ${FALSE}
Klachtoordeel, Gepubliceerd                           complaint-judgement   Gepubliceerd          ${FALSE}
Klachtoordeel, Gepland                                complaint-judgement   Gepland               ${FALSE}
Overig, Concept                                       other-publication     Concept               ${FALSE}
Overig, Gepubliceerd                                  other-publication     Gepubliceerd          ${FALSE}
Overig, Gepubliceerd, Bijlage                         other-publication     Gepubliceerd          ${TRUE}
Overig, Gepland                                       other-publication     Gepland               ${FALSE}
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
  [Arguments]  ${type}  ${publication_status}  ${has_attachment}  ${decision}=NotApplicable
  Create New Dossier  ${type}
  Generate Test Data Set  ${type}  ${has_attachment}
  Fill Out Basic Details  type=${type}
  IF  "${type}" == "woo-decision"
    Fill Out WooDecision Details  ${decision}  ${has_attachment}
    IF  "${decision}" == "Openbaarmaking"
      Upload Documents Step
    ELSE IF  "${decision}" == "Geen openbaarmaking"
      Click Save And Continue Production Report
    END
  ELSE IF  "${type}" == "covenant"
    Fill Out Covenant Details  ${has_attachment}
  ELSE IF  "${type}" == "disposition"
    Fill Out Disposition Details  ${has_attachment}
  ELSE IF  "${type}" == "annual-report"
    Fill Out Annual Report Details  ${has_attachment}
  ELSE IF  "${type}" == "investigation-report"
    Fill Out Investigation Report Details  ${has_attachment}
  ELSE IF  "${type}" == "complaint-judgement"
    Fill Out Complaint Judgement Details
  ELSE IF  "${type}" == "other-publication"
    Fill Out Other Publication Details  ${has_attachment}
  ELSE IF  "${type}" == "advice"
    Fill Out Advice Details  ${has_attachment}
  ELSE IF  "${type}" == "request-for-advice"
    Fill Out Request For Advice Details  ${has_attachment}
  END
  Publish Dossier And Return To Admin Home  ${publication_status}
