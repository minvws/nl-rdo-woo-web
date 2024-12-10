*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             OperatingSystem
Library             Process
Library             String
Library             DebugLibrary
Library             Browser


*** Keywords ***
Generic Download Click
  [Documentation]  Downloads a file by clicking on the selector, after which the download is verified if it's not 0 bytes.
  [Arguments]  ${selector}
  ${download_promise} =  Promise To Wait For Download
  Click  ${selector}
  ${file_object} =  Wait For  ${download_promise}
  File Should Exist  ${file_object}[saveAs]
  ${filesize} =  Get File Size  ${file_object}[saveAs]
  Log  File size of ${file_object.suggestedFilename}: ${filesize} bytes
  Should Be True  ${filesize} > 0  File size of ${file_object.suggestedFilename} should be greater than 0 bytes

Generic Download URL
  [Documentation]  Downloads a file through url, after which the download is verified if it's not 0 bytes.
  [Arguments]  ${url}
  ${file_object} =  Download  ${url}
  File Should Exist  ${file_object}[saveAs]
  ${filesize} =  Get File Size  ${file_object}[saveAs]
  Log  File size of ${file_object.suggestedFilename}: ${filesize} bytes
  Should Be True  ${filesize} > 0  File size of ${file_object.suggestedFilename} should be greater than 0 bytes

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
