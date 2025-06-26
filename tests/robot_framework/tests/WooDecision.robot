*** Settings ***
Documentation       Tests for the WooDecision information category.
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/Organisations.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  woodecision  pr

*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
In a public dossier with N public files, retract one of the documents
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  2
  Verify Document Retraction  1001
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er is 1 document ingetrokken.

In a public dossier with N public files, retract all documents via the Danger Zone
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Danger Zone Withdraw All Documents
  Verify Document Retraction  1001
  Verify Document Retraction  1002
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er zijn 2 documenten ingetrokken.

Upload a production report with N public files and a zip with N-1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A1
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/documenten - 10-1.zip
  Verify Document Upload Remaining  Nog te uploaden: 1 van 10 documenten.
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet  Er moet nog 1 document geüpload worden.


Upload a production report with N public files and a zip with N+1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A2
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/documenten - 10+1.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  This is a non-published document

Upload a production report with N public files and a zip with N other files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A3
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/documenten - 10 andere.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet  Er moeten nog 10 document(en) geüpload worden.

Upload a production report with N public files, M non-public files, and a zip with N + M files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A4
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 8 van 8 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien

Upload a production report with N public files, M already public files, and a zip with N + M files
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A5
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A5
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  ${TRUE}
  Verify Production Report Error  Regel 1: documentnummer 1001 bestaat al in een ander dossier
  Verify Production Report Error  Regel 2: documentnummer 1002 bestaat al in een ander dossier

In a public dossier with N public and M non-public documents, replace the production report with one where 1 non-public document has been made public
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=E2E-A6
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 9 openbaar 1 niet openbaar.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  1 bestaand document wordt aangepast.
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents
  Verify Document Upload Remaining  Nog te uploaden: 1 van 9 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/1008.pdf
  Wait For Elements State  //div[@data-e2e-name="has-changes"]  attached  timeout=30s
  Get Text  //div[@data-e2e-name="has-changes"]  contains  1 document toevoegen
  Get Text  //div[@data-e2e-name="has-changes"]  contains  0 documenten opnieuw publiceren
  Get Text  //div[@data-e2e-name="has-changes"]  contains  0 documenten vervangen
  Click  //button[@data-e2e-name="confirm-document-processing"]
  Click  //button[@data-e2e-name="back-to-uploading"]
  Wait For Elements State  //button[@data-e2e-name="back-to-uploading"]  detached
  Wait For Elements State  //div[@data-e2e-name="upload-busy"]  detached  timeout=30s
  Click Publications
  Get Text  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${DOSSIER_REFERENCE}')]  not contains  Incompleet

In a public dossier with N public and M non-public documents, replace the production report with one where 1 public document has been made non-public
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=E2E-A7
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 7 openbaar 3 niet openbaar.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  1 bestaand document wordt aangepast.
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents
  Open Document In Dossier  1009
  Verify Document History  Beoordeling aangepast naar niet openbaar
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Openbaar
  Click Public URL
  Verify Notification  besloten dit document niet openbaar te maken.

In a public dossier with N public files, replace the production report with one where 1 public document is suspended
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=E2E-A9
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 10 openbaar 1 opgeschort.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  1 bestaand document wordt aangepast.
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents
  Open Document In Dossier  1010
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Opgeschort
  Click Public URL
  Verify Notification
  ...  Er loopt nog een procedure over dit document met een betrokkene. We kunnen dit document daarom nog niet tonen.
  Verify Document History  Opgeschort
  Go To Admin
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en opgeschort  Er is 1 document opgeschort.

Create a publication that becomes public in the future
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A11
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Documents  tests/robot_framework/files/woodecision/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  ${timestamp} =  Get Current Date
  ${next_week} =  Add Time To Date  ${timestamp}  7 days
  Fill Publication Date  ${next_week}
  Click Save And Prepare
  ${today_localized} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${next_week_localized} =  Convert Timestamp Format  ${next_week}  time_format=d MMMM y  locale=nl
  Verify Publication Confirmation  ${today_localized}  ${next_week_localized}
  Click  //*[@data-e2e-name="dossier-public-dossier-link"]
  Verify Page Error  404

In a public dossier with N public files, replace the production report with a copy where one document is replaced with a new document
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=E2E-A12
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 10 openbaar waarvan 1 verwisseld.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  1001 mist in het productierapport


*** Keywords ***
Suite Setup
  Cleansheet  keep_prefixes=${False}
  Suite Setup Generic
  Login Admin
  Create Additional Prefixes
  Select Organisation

Suite Teardown
  No-Click Logout

Verify Document Retraction
  [Arguments]  ${document_id}
  Open Document In Dossier  ${document_id}
  Wait Until Keyword Succeeds
  ...  2 min
  ...  5 sec
  ...  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Ingetrokken
  Click Public URL
  Verify Notification  De reden dat het document is ingetrokken:
  Verify Document History  Ingetrokken met reden
  Go Back
  Go Back

Create Additional Prefixes
  [Documentation]  Creates new prefixes for testing purposes, including the default one because we assume a cleansheet -p was ran before.
  Click Organisation Selector
  Click Manage Organisations
  Open Organisation Details  E2E Test Organisation
  Select Responsible Department  E2E Test Department 1
  Add A New Organisation Prefix First Run  E2E-A
  Add A New Organisation Prefix  E2E-A1
  Add A New Organisation Prefix  E2E-A2
  Add A New Organisation Prefix  E2E-A3
  Add A New Organisation Prefix  E2E-A4
  Add A New Organisation Prefix  E2E-A5
  Add A New Organisation Prefix  E2E-A6
  Add A New Organisation Prefix  E2E-A7
  Add A New Organisation Prefix  E2E-A8
  Add A New Organisation Prefix  E2E-A9
  Add A New Organisation Prefix  E2E-A10
  Add A New Organisation Prefix  E2E-A11
  Add A New Organisation Prefix  E2E-A12
  Add A New Organisation Prefix  E2E-A13
  Click Save Prefixes

Verify Publication Status
  [Arguments]  ${dossier_reference}  ${expected_status}  ${expected_document_notification}
  Click Publications
  Click Publication By Value  ${dossier_reference}
  Get Text  //*[@data-e2e-name="has-document-notifications"]  contains  Documenten vereisen aandacht
  Get Text  //*[@data-e2e-name="document-notifications"]  contains  ${expected_document_notification}
  Click Publications
  Get Text
  ...  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${dossier_reference}')]
  ...  contains
  ...  ${expected_status}
