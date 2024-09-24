*** Settings ***
Documentation       Resource file with generic keywords.
Library             OperatingSystem
Library             Process
Library             Browser


*** Keywords ***
Download File
  [Arguments]  ${filename}  ${download_button_locator}
  ${document_location} =  Set Variable  ${OUTPUT_DIR}/${filename}
  ${dl_promise} =  Promise To Wait For Download  ${document_location}
  Click  ${download_button_locator}
  Wait For  ${dl_promise}
  File Should Exist  ${document_location}
  ${filesize} =  Get File Size  ${document_location}
  Log  File size of ${filename}: ${filesize} bytes
  Should Be True  ${filesize} > 0  File size of ${filename} should be greater than 0 bytes
  Remove File  ${document_location}

Click Download File Link
  ${filename} =  Get Text  xpath=//*[@data-e2e-name="file-name"]
  Download File  ${filename}  xpath=//*[@data-e2e-name="download-file-link"]

Verify Page Error
  [Arguments]  ${error_code}
  Get Text  //h2[@class="exception-http"]  contains  404
