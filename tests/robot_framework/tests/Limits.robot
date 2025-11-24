*** Settings ***
Documentation       Tests that verify that all the limits are working correctly.
Resource            ../resources/Covenant.resource
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Teardown       Clear TestData Folder
Test Tags           limits


*** Variables ***
${TEST_DATA_ROOT_LIMITS}    files/limits


*** Test Cases ***
Publish A WooDecision With More Than Max Number Of Documents
  ${new_prefix} =  Add A Random Organisation Prefix
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=${new_prefix}  type=woo-decision
  Fill Out WooDecision Details  Openbaarmaking
  Set Browser Timeout  5min
  Upload Production Report  ${TEST_DATA_ROOT_LIMITS}/production_report_50001.xlsx  expect_error=${TRUE}
  Verify Production Report Error  Het maximaal aantal documenten per dossier (50000) wordt overschreden

Publish A WooDecision With Max Individual Archive Size Of 5GB
  [Documentation]  Max is determined by multi-part upload, shoud be handled by FE
  ${test_data_location} =  Create Unique TestData Location
  ${archive} =  Generate A WooDecision Archive Based On Size  5000  ${test_data_location}
  ${production_report} =  Create Test Production Report  ${test_data_location}
  ${number_of_documents} =  Count Files In Directory  ${test_data_location}  pattern=*.txt
  ${new_prefix} =  Add A Random Organisation Prefix
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=${new_prefix}  type=woo-decision
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${production_report}
  Verify Document Upload Remaining  Nog te uploaden: ${number_of_documents} van ${number_of_documents} document
  Upload File By Selector  id=upload-area-dossier-files  ${archive}
  Wait For Condition
  ...  Attribute
  ...  //button[@data-e2e-name="process-documents"]
  ...  data-e2e-is-uploading
  ...  equals
  ...  false
  Get Text
  ...  //*[@data-e2e-name="invalid-files-warning"]
  ...  contains
  ...  Het bestand "archive.zip" werd genegeerd omdat het te groot is

Publish A Covenant With More Than Max Nr Of Attachments
  [Documentation]  Relates to #5112. Can also be more efficient with TXT files.
  Set Browser Timeout  30s
  Publish Test Covenant  has_attachment=${True}
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Edit Details
  WHILE  True  limit=49  on_limit=pass
    Upload Attachment  staatsblad
  END
  Get Element States  //*[@data-e2e-name="attachments"]//*[@data-e2e-name="add-file"]  contains  detached

Individual Files Of Max 1GB
  [Documentation]  Not allowed because of ClamAV limit of 1GB. Upload a zip with both a <1GB file and >1GB file, where only the smaller should be processed.
  ${new_prefix} =  Add A Random Organisation Prefix
  ${test_data_location} =  Create Unique TestData Location
  Generate File By Size  900  ${test_data_location}/900.txt
  Generate File By Size  1100  ${test_data_location}/1100.txt
  Create Zip From Files In Directory  ${test_data_location}  ${test_data_location}/archive.zip
  ${production_report} =  Create Test Production Report  ${test_data_location}
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=${new_prefix}  type=woo-decision
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${production_report}
  Verify Document Upload Remaining  Nog te uploaden: 2 van 2 documenten
  Set Browser Timeout  10min
  Upload And Process Documents  ${test_data_location}/archive.zip
  Verify Document Upload Remaining  Nog te uploaden: 1 van 2 documenten
  Get Text  //*[@data-e2e-name="missing-documents-list"]  contains  1100


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Generate File By Size
  [Arguments]  ${file_size_in_mb}  ${file_path}
  ${target_size_bytes} =  Evaluate  int(${file_size_in_mb}) * 1024 * 1024
  Create File  ${file_path}
  ${current_size} =  Get File Size  ${file_path}
  WHILE  ${current_size} < ${target_size_bytes}  limit=NONE
    ${random_text} =  FakerLibrary.Text  max_nb_chars=1000000
    Append To File  ${file_path}  ${random_text}
    ${current_size} =  Get File Size  ${file_path}
  END

Suite Teardown
  No-Click Logout
  Clear TestData Folder

Generate A WooDecision Archive Based On Size
  [Documentation]  Generates a zip file of specified size with copies of dummy-large.pdf, where each file has a unique name.
  [Arguments]  ${minimum_size_in_mb}  ${test_data_location}  ${archive_name}=archive.zip
  VAR  ${dummy_file} =  ${TEST_DATA_ROOT_LIMITS}/dummy-large.txt
  ${folder_size} =  Get Folder Size  ${test_data_location}
  WHILE  ${folder_size} < ${minimum_size_in_mb}
    ${file_name} =  Generate Random Filename
    Copy File  ${dummy_file}  ${test_data_location}/${file_name}.txt
    ${folder_size} =  Get Folder Size  ${test_data_location}
  END
  Create Zip From Files In Directory  ${test_data_location}  ${test_data_location}/${archive_name}
  RETURN  ${test_data_location}/${archive_name}

Get Folder Size
  [Arguments]  ${folder_path}
  ${output} =  Run  du -sm ${folder_path}
  ${size_kilobytes} =  Convert To Integer  ${output.split()[0]}
  RETURN  ${size_kilobytes}
