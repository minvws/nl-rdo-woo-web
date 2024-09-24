*** Settings ***
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/Document.resource
Resource            ../resources/Covenant.resource
Resource            ../resources/Disposition.resource
Resource            ../resources/AnnualReport.resource
Resource            ../resources/InvestigationReport.resource
Resource            ../resources/ComplaintJudgement.resource
Library             Collections
Suite Setup         Suite Setup
Suite Teardown      Logout Admin
Test Teardown       Run Keyword If Test Failed  Go To Admin
Test Template       Create Test Dossier
Test Tags           testdossiers  ci


*** Variables ***
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie/dossiers
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag
${INVENTORY}            ${EMPTY}
${DOCUMENTS}            ${EMPTY}
${NUMBER_OF_DOCUMENTS}  ${EMPTY}
${ATTACHMENT_LOCATION}  ${EMPTY}


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


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin
  Select Organisation

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
  END
  Publish Dossier And Return To Admin Home  ${publication_status}

Parse Dataset To File Paths
  [Arguments]  ${dataset}  ${type}  ${has_attachment}
  ${files} =  List Files In Directory  tests/robot_framework/files/testdossiers/${dataset}  pattern=VWS*.pdf
  IF  '${type}' == 'woo-decision'
    Set Test Variable  ${INVENTORY}  tests/robot_framework/files/testdossiers/${dataset}/inventory.xlsx
    Set Test Variable  ${DOCUMENTS}  tests/robot_framework/files/testdossiers/${dataset}/Archive.zip
    Set Test Variable  ${NUMBER_OF_DOCUMENTS}  5
  ELSE IF  '${type}' == 'covenant'
    ${covenant_file} =  Get From List  ${files}  0
    Set Test Variable  ${COVENANT_FILENAME}  ${covenant_file}
    Set Test Variable  ${COVENANT_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${covenant_file}
  ELSE IF  '${type}' == 'disposition'
    ${disposition_file} =  Get From List  ${files}  0
    Set Test Variable  ${DISPOSITION_FILENAME}  ${disposition_file}
    Set Test Variable  ${DISPOSITION_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${disposition_file}
  ELSE IF  '${type}' == 'annual-report'
    ${annual_report_file} =  Get From List  ${files}  0
    Set Test Variable  ${ANNUAL_REPORT_FILENAME}  ${annual_report_file}
    Set Test Variable
    ...  ${ANNUAL_REPORT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${annual_report_file}
  ELSE IF  '${type}' == 'investigation-report'
    ${investigation_report_file} =  Get From List  ${files}  0
    Set Test Variable  ${INVESTIGATION_REPORT_FILENAME}  ${investigation_report_file}
    Set Test Variable
    ...  ${INVESTIGATION_REPORT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${investigation_report_file}
  ELSE IF  '${type}' == 'complaint-judgement'
    ${complaint_judgement_file} =  Get From List  ${files}  0
    Set Test Variable  ${COMPLAINT_JUDGEMENT_FILENAME}  ${complaint_judgement_file}
    Set Test Variable
    ...  ${COMPLAINT_JUDGEMENT_LOCATION}
    ...  tests/robot_framework/files/testdossiers/${dataset}/${complaint_judgement_file}
  END
  IF  ${has_attachment}
    # Woo-decision has only one VWS* file, while other types have two
    IF  '${type}' == 'woo-decision'
      ${attachment_file_index} =  Set Variable  0
    ELSE
      ${attachment_file_index} =  Set Variable  1
    END
    ${attachment_file} =  Get From List  ${files}  ${attachment_file_index}
    Set Test Variable  ${ATTACHMENT_FILENAME}  ${attachment_file}
    Set Test Variable  ${ATTACHMENT_LOCATION}  tests/robot_framework/files/testdossiers/${dataset}/${attachment_file}
  END
