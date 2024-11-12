*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             OperatingSystem
Library             Process
Library             String
Library             DebugLibrary
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

Convert Dutch To English Date
  [Documentation]  Converts a date string like '1 oktober 2024' to '1-10-2024'
  [Arguments]  ${dutch_timestamp}
  # Manually replace Dutch month with English month
  ${dutch_timestamp} =  Strip String  ${dutch_timestamp}
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  januari  January
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  februari  February
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  maart  March
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  april  April
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  mei  May
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  juni  June
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  juli  July
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  augustus  August
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  september  September
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  oktober  October
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  november  November
  ${dutch_timestamp} =  Replace String  ${dutch_timestamp}  december  December
  ${datetime_string} =  Convert Date  date=${dutch_timestamp}  date_format=%d %B %Y  result_format=%d-%m-%Y
  RETURN  ${datetime_string}

Input Date Using JavaScript
  [Documentation]  Has/is used in 'Select Filter Options - Date' to circumvent UI flickering
  [Arguments]  ${id}  ${value}
  Evaluate JavaScript  id=${id}  document.getElementById('${id}').value = '${value}'

Click With Reload Retry
  [Arguments]  ${selector}  ${sleep}=5s
  VAR  ${count}  0
  WHILE  ${count} < 1  limit=30 seconds
    Sleep  ${sleep}
    Reload
    ${count} =  Get Element Count  ${selector}
  END
  Click  ${selector}
