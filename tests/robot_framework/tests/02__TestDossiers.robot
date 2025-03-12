*** Comments ***
# robocop: off=no-test-variable


*** Settings ***
Documentation       Tests that create all types of dossiers with a few variations.
...                 This is named 02 because we want to run this before we run the 03 Public suite, so we have content to search for.
...                 This does not run a cleansheet, so you might get errors when running this for a second time.
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/Covenant.resource
Resource            ../resources/Disposition.resource
Resource            ../resources/AnnualReport.resource
Resource            ../resources/InvestigationReport.resource
Resource            ../resources/ComplaintJudgement.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Subjects.resource
Resource            ../resources/OtherPublication.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Advice.resource
Library             DependencyLibrary
Suite Setup         Suite Setup
Suite Teardown      No-Click Logout
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           ci  testdossiers  public-init  themes-init


*** Test Cases ***                                    type                  publication_status    has_attachment    dataset   decision
Woo-besluit, Concept, Openbaarmaking                  woo-decision          Concept               ${FALSE}          woo1      Openbaarmaking
Woo-besluit, Concept, Geen openbaarmaking             woo-decision          Concept               ${FALSE}          woo2      Geen openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking             woo-decision          Gepubliceerd          ${FALSE}          woo3      Openbaarmaking
Woo-besluit, Gepubliceerd, Openbaarmaking, Bijlage    woo-decision          Gepubliceerd          ${TRUE}           woo4      Openbaarmaking
Woo-besluit, Gepubliceerd, Geen openbaarmaking        woo-decision          Gepubliceerd          ${FALSE}          woo5      Geen openbaarmaking
Woo-besluit, Gepland, Openbaarmaking                  woo-decision          Gepland               ${FALSE}          woo6      Openbaarmaking
Woo-besluit, Gepland, Geen openbaarmaking             woo-decision          Gepland               ${FALSE}          woo7      Geen openbaarmaking
Convenant, Concept                                    covenant              Concept               ${FALSE}          cov1
Convenant, Gepubliceerd                               covenant              Gepubliceerd          ${FALSE}          cov2
Convenant, Gepubliceerd, Bijlage                      covenant              Gepubliceerd          ${TRUE}           cov3
Convenant, Gepland                                    covenant              Gepland               ${FALSE}          cov4
Beschikking, Concept                                  disposition           Concept               ${FALSE}          bes1
Beschikking, Gepubliceerd                             disposition           Gepubliceerd          ${FALSE}          bes2
Beschikking, Gepubliceerd, Bijlage                    disposition           Gepubliceerd          ${TRUE}           bes3
Beschikking, Gepland                                  disposition           Gepland               ${FALSE}          bes4
Jaarplan, Concept                                     annual-report         Concept               ${FALSE}          jp1
Jaarplan, Gepubliceerd                                annual-report         Gepubliceerd          ${FALSE}          jp2
Jaarplan, Gepubliceerd, Bijlage                       annual-report         Gepubliceerd          ${TRUE}           jp3
Jaarplan, Gepland                                     annual-report         Gepland               ${FALSE}          jp4
Onderzoeksrapport, Concept                            investigation-report  Concept               ${FALSE}          or1
Onderzoeksrapport, Gepubliceerd                       investigation-report  Gepubliceerd          ${FALSE}          or2
Onderzoeksrapport, Gepubliceerd, Bijlage              investigation-report  Gepubliceerd          ${TRUE}           or3
Onderzoeksrapport, Gepland                            investigation-report  Gepland               ${FALSE}          or4
Klachtoordeel, Concept                                complaint-judgement   Concept               ${FALSE}          ko1
Klachtoordeel, Gepubliceerd                           complaint-judgement   Gepubliceerd          ${FALSE}          ko2
Klachtoordeel, Gepland                                complaint-judgement   Gepland               ${FALSE}          ko4
Overig, Concept                                       other-publication     Concept               ${FALSE}          ot1
Overig, Gepubliceerd                                  other-publication     Gepubliceerd          ${FALSE}          ot2
Overig, Gepubliceerd, Bijlage                         other-publication     Concept               ${TRUE}           ot3
# Advies, Concept  advice  Concept  ${FALSE}  ad1
# Advies, Gepubliceerd  advice  Gepubliceerd  ${FALSE}  ad2
# Advies, Gepubliceerd, Bijlage  advice  Concept  ${TRUE}  ad3
# Advies, Gepland  advice  Concept  ${FALSE}  ad4


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin
  Select Organisation
  Create Random Subjects
  Click Publications

Create Test Dossier
  [Arguments]  ${type}  ${publication_status}  ${has_attachment}  ${dataset}  ${decision}=NotApplicable
  Create New Dossier  ${type}
  Parse Dataset To File Paths  ${dataset}  ${type}  ${has_attachment}
  Fill Out Basic Details  type=${type}
  IF  "${type}" == "woo-decision"
    Fill Out Decision Details  ${decision}  ${has_attachment}
    IF  "${decision}" == "Openbaarmaking"  Upload Documents Step
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
    Fill Out Other Publication Details
  ELSE IF  "${type}" == "advice"
    Fill Out Advice Details
  END
  Publish Dossier And Return To Admin Home  ${publication_status}

Parse Dataset To File Paths
  [Arguments]  ${dataset}  ${type}  ${has_attachment}
  ${files} =  List Files In Directory  tests/robot_framework/files/testdossiers/${dataset}  pattern=VWS*.pdf
  IF  '${type}' == 'woo-decision'
    VAR  ${PRODUCTION_REPORT}  tests/robot_framework/files/testdossiers/${dataset}/inventory.xlsx  scope=test
    VAR  ${DOCUMENTS}  tests/robot_framework/files/testdossiers/${dataset}/Archive.zip  scope=test
    VAR  ${NUMBER_OF_DOCUMENTS}  5  scope=test
  ELSE IF  '${type}' == 'covenant'
    ${covenant_file} =  Get From List  ${files}  0
    VAR  ${COVENANT_FILENAME}  ${covenant_file}  scope=test
    VAR  ${COVENANT_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${covenant_file}  scope=test
  ELSE IF  '${type}' == 'disposition'
    ${disposition_file} =  Get From List  ${files}  0
    VAR  ${DISPOSITION_FILENAME}  ${disposition_file}  scope=test
    VAR  ${DISPOSITION_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${disposition_file}  scope=test
  ELSE IF  '${type}' == 'annual-report'
    ${annual_report_file} =  Get From List  ${files}  0
    VAR  ${ANNUAL_REPORT_FILENAME}  ${annual_report_file}  scope=test
    VAR
    ...  ${ANNUAL_REPORT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${annual_report_file}
    ...  scope=test
  ELSE IF  '${type}' == 'investigation-report'
    ${investigation_report_file} =  Get From List  ${files}  0
    VAR  ${INVESTIGATION_REPORT_FILENAME}  ${investigation_report_file}  scope=test
    VAR
    ...  ${INVESTIGATION_REPORT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${investigation_report_file}
    ...  scope=test
  ELSE IF  '${type}' == 'complaint-judgement'
    ${complaint_judgement_file} =  Get From List  ${files}  0
    VAR  ${COMPLAINT_JUDGEMENT_FILENAME}  ${complaint_judgement_file}  scope=test
    VAR
    ...  ${COMPLAINT_JUDGEMENT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${complaint_judgement_file}
    ...  scope=test
  ELSE IF  '${type}' == 'other-publication'
    ${other_publication_file} =  Get From List  ${files}  0
    VAR  ${OTHER_PUBLICATION_FILENAME}  ${other_publication_file}  scope=test
    VAR
    ...  ${OTHER_PUBLICATION_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${other_publication_file}
    ...  scope=TEST
  ELSE IF  '${type}' == 'advice'
    ${advice_file} =  Get From List  ${files}  0
    VAR  ${ADVICE_FILENAME}  ${advice_file}  scope=TEST
    VAR  ${ADVICE_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${advice_file}  scope=TEST
  END
  IF  ${has_attachment}
    # Woo-decision has only one VWS* file, while other types have two
    IF  '${type}' == 'woo-decision'
      ${attachment_file_index} =  Set Variable  0
    ELSE
      ${attachment_file_index} =  Set Variable  1
    END
    ${attachment_file} =  Get From List  ${files}  ${attachment_file_index}
    VAR  ${ATTACHMENT_FILENAME}  ${attachment_file}  scope=test
    VAR  ${ATTACHMENT_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${attachment_file}  scope=test
  END
