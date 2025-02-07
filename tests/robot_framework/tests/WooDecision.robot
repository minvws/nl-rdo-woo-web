*** Settings ***
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/Organisations.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
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
  Fill Out Basic Details  prefix=MINVWS1
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document  tests/robot_framework/files/woodecision/documenten - 10-1.zip
  Verify Document Upload Remaining  Nog te uploaden: 1 van 10 documenten.

Upload a production report with N public files and a zip with N+1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS2
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document  tests/robot_framework/files/woodecision/documenten - 10+1.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  Dummy PDF file

Upload a production report with N public files and a zip with N other files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS3
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document  tests/robot_framework/files/woodecision/documenten - 10 andere.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.

Upload a production report with N public files, M non-public files, and a zip with N + M files
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS4
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 8 van 8 documenten.
  Upload Document  tests/robot_framework/files/woodecision/documenten - 10.zip
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien

Upload a production report with N public files, M already public files, and a zip with N + M files
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 2.zip
  ...  number_of_documents=2
  ...  prefix=MINVWS5
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS5
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  ${TRUE}
  Verify Production Report Error  Regel 1: documentnummer 1001 bestaat al in een ander dossier
  Verify Production Report Error  Regel 2: documentnummer 1002 bestaat al in een ander dossier

In a public dossier with N public and M non-public documents, replace the production report with one where 1 non-public document has been made public
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=MINVWS6
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

In a public dossier with N public and M non-public documents, replace the production report with one where 1 public document has been made non-public
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=MINVWS7
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

In a public dossier with N public files, retract one of the documents
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 8.zip
  ...  number_of_documents=8
  ...  prefix=MINVWS8
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
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=MINVWS9
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
  Go To Admin  # So the teardown works..

In a public dossier with N public files, retract all documents via the Danger Zone
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=MINVWS10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Danger Zone Withdraw All Documents
  Verify Document Retraction  1001
  Verify Document Retraction  1005
  Verify Document Retraction  1010

Create a publication that becomes public in the future
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS11
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload Document  tests/robot_framework/files/woodecision/documenten - 10.zip
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
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=MINVWS12
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report
  ...  tests/robot_framework/files/woodecision/productierapport - 10 openbaar waarvan 1 verwisseld.xlsx
  ...  ${TRUE}
  Verify Production Report Replace  1001 mist in het productierapport

Retract a document that has already been published
  Publish Test Dossier
  ...  production_report=tests/robot_framework/files/woodecision/productierapport - 10 openbaar.xlsx
  ...  documents=tests/robot_framework/files/woodecision/documenten - 10.zip
  ...  number_of_documents=10
  ...  prefix=MINVWS13
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  1001
  Retract Document
  Click Breadcrumb Element  2
  Verify Document Retraction  1001


*** Keywords ***
Suite Setup
  Cleansheet  keep_prefixes=${False}
  Suite Setup - CI
  Login Admin
  Create Additional Prefixes
  Select Organisation

Suite Teardown
  Go To Admin
  Logout Admin

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

Create Additional Prefixes
  [Documentation]  Creates new prefixes for testing purposes, including the default one because we assume a cleansheet -p was ran before.
  Click Organisation Selector
  Click Manage Organisations
  Open Organisation Details  Programmadirectie Openbaarheid
  Select Responsible Department  ministerie van Volksgezondheid, Welzijn en Sport
  Add A New Organisation Prefix  MINVWS
  Add A New Organisation Prefix  MINVWS1
  Add A New Organisation Prefix  MINVWS2
  Add A New Organisation Prefix  MINVWS3
  Add A New Organisation Prefix  MINVWS4
  Add A New Organisation Prefix  MINVWS5
  Add A New Organisation Prefix  MINVWS6
  Add A New Organisation Prefix  MINVWS7
  Add A New Organisation Prefix  MINVWS8
  Add A New Organisation Prefix  MINVWS9
  Add A New Organisation Prefix  MINVWS10
  Add A New Organisation Prefix  MINVWS11
  Add A New Organisation Prefix  MINVWS12
  Add A New Organisation Prefix  MINVWS13
  Click Save Prefixes
