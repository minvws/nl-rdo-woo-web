*** Settings ***
Documentation       Tests for the WooDecision information category.
Resource            ../resources/Dossier.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  woodecision  pr


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
In A Public Dossier With N Public Files, Retract One Of The Documents
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  2
  Verify Document Retraction  1001
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er is 1 document ingetrokken.

In A Public Dossier With N Public Files, Retract All Documents Via The Danger Zone
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Danger Zone Withdraw All Documents
  Verify Document Retraction  1001
  Verify Document Retraction  1002
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er zijn 2 documenten ingetrokken.

Upload A Production Report With N Public Files And A Zip With N-1 Files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A1
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  files/woodecision/documenten - 10-1.zip
  Verify Document Upload Remaining  Nog te uploaden: 1 van 10 documenten.
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet  Er moet nog 1 document geüpload worden.

Upload A Production Report With N Public Files And A Zip With N+1 Files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A2
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  files/woodecision/documenten - 10+1.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  This is a non-published document

Upload A Production Report With N Public Files And A Zip With N Other Files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A3
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  files/woodecision/documenten - 10 andere.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet  Er moeten nog 10 document(en) geüpload worden.

Upload A Production Report With N Public Files, M Non-public Files, And A Zip With N + M Files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A4
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 8 van 8 documenten.
  Upload And Process Documents  files/woodecision/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien

Upload A Production Report With N Public Files, M Already Public Files, And A Zip With N + M Files
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A5
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A5
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report
  ...  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  ${TRUE}
  Verify Production Report Error  Regel 1: documentnummer 1001 bestaat al in een ander dossier
  Verify Production Report Error  Regel 2: documentnummer 1002 bestaat al in een ander dossier

In A Public Dossier With N Public And M Non-public Documents, Replace The Production Report With One Where 1 Non-public Document Has Been Made Public
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=E2E-A6
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  files/woodecision/productierapport - 9 openbaar 1 niet openbaar.xlsx
  ...  1 bestaand document wordt aangepast.
  Verify Document Upload Remaining  Nog te uploaden: 1 van 9 documenten.
  Upload And Process Documents  files/woodecision/1008.pdf
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

In A Public Dossier With N Public And M Non-public Documents, Replace The Production Report With One Where 1 Public Document Has Been Made Non-public
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=E2E-A7
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  files/woodecision/productierapport - 7 openbaar 3 niet openbaar.xlsx
  ...  1 bestaand document wordt aangepast.
  Open Document In Dossier  1009
  Verify Document History  Beoordeling aangepast naar niet openbaar
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Openbaar
  Click Public URL
  Verify Notification  besloten dit document niet openbaar te maken.

In A Public Dossier With N Public Files, Replace The Production Report With One Where 1 Public Document Is Suspended
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=E2E-A9
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  files/woodecision/productierapport - 10 openbaar 1 opgeschort.xlsx
  ...  1 bestaand document wordt aangepast.
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

Create A Publication That Becomes Public In The Future
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A11
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  files/woodecision/documenten - 10.zip
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

In A Public Dossier With N Public Files, Replace The Production Report With A Copy Where One Document Is Replaced With A New Document
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=E2E-A12
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  files/woodecision/productierapport - 10 openbaar waarvan 1 verwisseld.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  1001 mist in het productierapport

The Content Of The Published Pdf Should Not Show Up In The Admin Search
  Publish Test WooDecision
  ...  production_report=files/woodecision/halieborabotttejetoe/productierapport.xlsx
  ...  documents=files/woodecision/halieborabotttejetoe/3453455.pdf
  ...  number_of_documents=1
  ...  prefix=E2E-A12
  Verify Admin Search Results  halieborabotttejetoe  0
  Verify Admin Search Results  random  1
  Verify Admin Search Results  Robot  1

Retract A Document And Then Make It Non-public
  Publish Test WooDecision
  ...  production_report=files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=E2E-A13
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  2
  Verify Document Retraction  1001
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er is 1 document ingetrokken.
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  files/woodecision/productierapport - 2 niet openbaar.xlsx
  ...  2 bestaande documenten worden aangepast.
  Click Breadcrumb Element  1
  Verify Publication Action Status  ${DOSSIER_REFERENCE}  ${EMPTY}


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
  Verify Publication Action Status  ${dossier_reference}  ${expected_status}

Replace Production Report
  [Arguments]  ${replacement_production_report}  ${expected_replacement_message}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  ${replacement_production_report}  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  ${expected_replacement_message}
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents
