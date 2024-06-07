*** Settings ***
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Public.resource
Suite Setup         Suite Setup - CI
Test Setup          Test Setup
Test Teardown       Logout Admin
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
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Status  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10-1.zip
  Verify Document Upload Status  Nog te uploaden: 1 van 10 documenten.

Upload a production report with N public files and a zip with N+1 files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Status  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10+1.zip
  Verify Document Upload Status  Alle documenten uit het productierapport zijn geüpload.
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  Dummy PDF file
  Go To Admin  # So the teardown works..

Upload a production report with N public files and a zip with N other files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/robot_framework/files/productierapport - 10 openbaar.xlsx
  Verify Document Upload Status  Nog te uploaden: 10 van 10 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10 andere.zip
  Verify Document Upload Status  Nog te uploaden: 10 van 10 documenten.

Upload a production report with N public files, M non-public files, and a zip with N + M files
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Document Upload Status  Nog te uploaden: 8 van 8 documenten.
  Upload Document Zip  tests/robot_framework/files/documenten - 10.zip
  Verify Document Upload Status  Alle documenten uit het productierapport zijn geüpload.
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien
  Go To Admin  # So the teardown works..

Upload a production report with N public files, M already public files, and a zip with N + M files
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 2 openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 2.zip
  ...  number_of_documents=2
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  Verify Inventory Error  Regel 1: documentnummer 1001 bestaat al in een ander dossier
  Verify Inventory Error  Regel 2: documentnummer 1002 bestaat al in een ander dossier

In a public dossier with N public and M non-public documents, replace the production report with one where 1 non-public document has been made public
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  documents=tests/robot_framework/files/documenten - 8.zip
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Document Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/productierapport - 9 openbaar 1 niet openbaar.xlsx
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  1 bestaand document wordt aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  Verify Document Upload Status  Nog te uploaden: 1 van 9 documenten.


*** Keywords ***
Test Setup
  Cleansheet
  Login Admin

Publish Test Dossier
  [Arguments]  ${inventory}  ${documents}  ${number_of_documents}
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  ${inventory}
  Verify Document Upload Status  Nog te uploaden: ${number_of_documents} van ${number_of_documents} documenten.
  Upload Document Zip  ${documents}
  Verify Document Upload Status  Alle documenten uit het productierapport zijn geüpload.
  Publish Dossier And Return To Admin Home
