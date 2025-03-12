*** Settings ***
Documentation       Tests that focus on testing the different supported file types for WooDecisions.
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Organisations.resource
Suite Setup         Suite Setup
Test Setup          Go To Admin
Test Tags           ci  filetypes


*** Test Cases ***
Create a dossier with different filetypes using individual files, zip and 7z
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=MINVWS
  Fill Out Decision Details  Openbaarmaking
  Upload Production Report  tests/robot_framework/files/filetypes/productierapport.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 15 van 15 documenten.
  Upload Documents  tests/robot_framework/files/filetypes/presentation.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 15 documenten.
  Upload Documents  tests/robot_framework/files/filetypes/text.7z
  Verify Document Upload Remaining  Nog te uploaden: 8 van 15 documenten.
  VAR  @{files} =
  ...  tests/robot_framework/files/filetypes/16101.docx
  ...  tests/robot_framework/files/filetypes/16102.doc
  ...  tests/robot_framework/files/filetypes/16103.odt
  Upload Documents  @{files}
  VAR  @{files} =
  ...  tests/robot_framework/files/filetypes/16109.xlsx
  ...  tests/robot_framework/files/filetypes/16110.xls
  ...  tests/robot_framework/files/filetypes/16111.csv
  Upload Documents  @{files}
  VAR  @{files} =
  ...  tests/robot_framework/files/filetypes/16112.ods
  ...  tests/robot_framework/files/filetypes/16115.pdf
  Upload Documents  @{files}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home

Verify filetypes of uploaded dossier
  Wait For Queue To Empty
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Verify Document Filetype  16101  Word  DOCX
  Verify Document Filetype  16102  Word  DOC
  Verify Document Filetype  16103  Word  ODT
  Verify Document Filetype  16104  Presentatie  PPTX
  Verify Document Filetype  16105  Presentatie  PPT
  Verify Document Filetype  16106  Presentatie  ODP
  Verify Document Filetype  16107  Presentatie  PPS
  Verify Document Filetype  16108  Presentatie  PPSX
  Verify Document Filetype  16109  Spreadsheet  XLSX
  Verify Document Filetype  16110  Spreadsheet  XLS
  Verify Document Filetype  16111  Spreadsheet  CSV
  Verify Document Filetype  16112  Spreadsheet  ODS
  Verify Document Filetype  16113  Onbekend  TXT
  Verify Document Filetype  16114  Onbekend  RDF
  Verify Document Filetype  16115  PDF  PDF

Verify filetypes available in search
  [Template]  Search On Public For
  16101.docx  Documentnummer 16101
  16102.doc  Documentnummer 16102
  16103.odt  Documentnummer 16103
  16109.xlsx  Documentnummer 16109
  16110.xlsx  Documentnummer 16110


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup - CI
  Login Admin
  Select Organisation  organisation=Programmadirectie Openbaarheid
