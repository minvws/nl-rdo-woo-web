*** Settings ***
Documentation       Tests that focus on testing the different supported file types for WooDecisions.
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Suite Setup         Suite Setup
Test Setup          Go To Admin
Test Tags           ci  filetypes


*** Test Cases ***
Create A Dossier With Different Filetypes Using Individual Files, Zip And 7z
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=E2E-A
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  files/filetypes/productierapport.xlsx
  Verify Document Upload Remaining  Nog te uploaden: 15 van 15 documenten.
  Upload And Process Documents  files/filetypes/presentation.zip
  Verify Document Upload Remaining  Nog te uploaden: 10 van 15 documenten.
  Upload And Process Documents  files/filetypes/text.7z
  Verify Document Upload Remaining  Nog te uploaden: 8 van 15 documenten.
  VAR  @{files} =
  ...  files/filetypes/16101.docx
  ...  files/filetypes/16102.doc
  ...  files/filetypes/16103.odt
  Upload And Process Documents  @{files}
  Verify Document Upload Remaining  Nog te uploaden: 5 van 15 documenten.
  VAR  @{files} =
  ...  files/filetypes/16109.xlsx
  ...  files/filetypes/16110.xls
  ...  files/filetypes/16111.csv
  Upload And Process Documents  @{files}
  Verify Document Upload Remaining  Nog te uploaden: 2 van 15 documenten.
  VAR  @{files} =
  ...  files/filetypes/16112.ods
  ...  files/filetypes/16115.pdf
  Upload And Process Documents  @{files}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home

Verify Filetypes Of Uploaded Dossier
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

Verify Filetypes Available In Search
  [Template]  Search On Public For
  16101.docx  16101
  16102.doc  16102
  16103.odt  16103
  16109.xlsx  16109
  16110.xlsx  16110


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Select Organisation  organisation=E2E Test Organisation
