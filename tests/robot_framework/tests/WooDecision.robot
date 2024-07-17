*** Settings ***
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Document.resource
Resource            ../resources/Public.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Teardown       Cleansheet
Test Tags           ci  woodecision


*** Variables ***
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie/dossiers
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
Upload a production report with N public files and a zip with N-1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10-1.zip
  Verify Document Upload Remaining  Nog te uploaden: 1 van 10 documenten.

Upload a production report with N public files and a zip with N+1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10+1.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  Dummy PDF file

Upload a production report with N public files and a zip with N other files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10 andere.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.

Upload a production report with N public files, M non-public files, and a zip with N + M files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 8 van 8 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien

Upload a production report with N public files, M already public files, and a zip with N + M files
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 2.zip
  ...  number_of_documents=2
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx  ${TRUE}
  Verify Inventory Error  Regel 1: documentnummer 1001 bestaat al in een ander dossier
  Verify Inventory Error  Regel 2: documentnummer 1002 bestaat al in een ander dossier

In a public dossier with N public and M non-public documents, replace the production report with one where 1 non-public document has been made public
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 8.zip
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/productierapport - 9 openbaar 1 niet openbaar.xlsx  ${TRUE}
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  1 bestaand document wordt aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  Verify Document Upload Remaining  Nog te uploaden: 1 van 9 documenten.

In a public dossier with N public and M non-public documents, replace the production report with one where 1 public document has been made non-public
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 8.zip
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/productierapport - 7 openbaar 3 niet openbaar.xlsx  ${TRUE}
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  1 bestaand document wordt aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  Open Document In Dossier  1009
  Verify Document History  Beoordeling aangepast naar niet openbaar
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Gepubliceerd
  Click Public URL
  Verify Notification
  ...  Tijdens de beoordeling van het Woo-verzoek heeft Ministerie van Volksgezondheid, Welzijn en Sport besloten dit document niet openbaar te maken.

In a public dossier with N public files, retract one of the documents
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 8.zip
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  3
  Click Public URL
  Verify Notification  Dit document is op dit moment niet beschikbaar.
  Verify Document History  Ingetrokken met reden
  Go To Admin  # So the teardown works..

In a public dossier with N public files, replace the production report with one where 1 public document is suspended
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 10.zip
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar 1 opgeschort.xlsx  ${TRUE}
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  1 bestaand document wordt aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  Open Document In Dossier  1010
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Opgeschort
  Click Public URL
  Verify Notification
  ...  Er loopt nog een procedure over dit document met een betrokkene. We kunnen dit document daarom nog niet tonen.
  Verify Document History
  ...  Opgeschort  # does not work yet?! See https://github.com/minvws/nl-rdo-woo-web-private/issues/2682
  Go To Admin  # So the teardown works..

In a public dossier with N public files, retract all documents via the Danger Zone
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 10.zip
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Danger Zone Withdraw All Documents
  Verify Document Retraction  1001
  Verify Document Retraction  1005
  Verify Document Retraction  1010

Create a publication that becomes public in the future
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  ${timestamp} =  Get Current Date
  ${next_week} =  Add Time To Date  ${timestamp}  7 days
  Fill Publication Date  ${next_week}
  Click Save And Prepare
  ${today_localized} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${next_week_localized} =  Convert Timestamp Format  ${next_week}  time_format=d MMMM y  locale=nl
  Verify Publication Confirmation  ${today_localized}  ${next_week_localized}
  # TODO: Click the Public URL without getting a 404?

In a public dossier with N public files, replace the production report with a copy where one document is replaced with a new document
  [Tags]  deze
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 10.zip
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar waarvan 1 verwisseld.xlsx  ${TRUE}
  Verify Inventory Replace  1001 mist in het productierapport

Retract a document that has already been published
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 10.zip
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  2
  Verify Document Retraction  1001


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin

Suite Teardown
  Go To Admin
  Logout Admin

Publish Test Dossier
  [Arguments]  ${inventory}  ${documents}  ${number_of_documents}
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  ${inventory}
  Verify Document Upload Remaining  Nog te uploaden: ${number_of_documents} van ${number_of_documents} documenten.
  Upload Document Zip  ${documents}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home

Verify Document Retraction
  [Arguments]  ${document_id}
  Open Document In Dossier  ${document_id}
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Ingetrokken
  Click Public URL
  Verify Notification  De reden dat het document is ingetrokken:
  Verify Document History  Ingetrokken met reden
  Go Back
  Go Back
