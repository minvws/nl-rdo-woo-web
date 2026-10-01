*** Settings ***
Documentation       Resource file with generic keywords.
Library             Collections
Library             DateTime
Library             OperatingSystem
Library             Process
Library             String
Library             Browser
Library             DebugLibrary


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Keywords ***
Generic Download Click
  [Documentation]  Downloads a file by clicking on the selector, after which the download is verified if it's not 0 bytes.
  [Arguments]  ${selector}  ${expected_extension}=${EMPTY}
  ${download_promise} =  Promise To Wait For Download
  Click  ${selector}
  ${file_object} =  Wait For  ${download_promise}
  File Should Exist  ${file_object}[saveAs]
  ${filesize} =  Get File Size  ${file_object}[saveAs]
  Log  File size of ${file_object.suggestedFilename}: ${filesize} bytes
  Should Be True  ${filesize} > 0  File size of ${file_object.suggestedFilename} should be greater than 0 bytes
  IF  '${expected_extension}' != ''
    ${expected_extension} =  Convert To Lower Case  ${expected_extension}
    ${extension} =  Split Extension  ${file_object}[suggestedFilename]
    Should Be Equal
    ...  ${extension}[1]
    ...  ${expected_extension}
    ...  ignore_case=${TRUE}
    ...  msg=Filename '${file_object}[suggestedFilename]' should contain the extension '.${expected_extension}'
  END
  ${file_name} =  Rename Downloaded Object To Original File Name  ${file_object}
  RETURN  ${file_name}

Rename Downloaded Object To Original File Name
  [Arguments]  ${file_object}
  ${path}  ${_} =  Split Path  ${file_object}[saveAs]
  Move File  source=${file_object}[saveAs]  destination=${path}/${file_object}[suggestedFilename]
  RETURN  ${path}/${file_object}[suggestedFilename]

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
  ${symfony_error_box} =  Get Element Count  //h2[@class="exception-http"]
  IF  ${symfony_error_box} > 0
    Get Text  //h2[@class="exception-http"]  contains  ${error_code}
  ELSE
    Get Text  //h1  contains  Pagina niet gevonden
  END

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

Click With Reload Retry
  [Arguments]  ${selector}  ${sleep}=5s
  VAR  ${count} =  0
  WHILE  ${count} < 1  limit=30 seconds
    Sleep  ${sleep}
    Reload
    ${count} =  Get Element Count  ${selector}
  END
  Click  ${selector}

Click With CSRF Retry
  [Documentation]  Temporary workaround for a known, still-open bug where a CSRF error is intermittently
  ...  shown after clicking a form submit button: https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
  ...  Remove this keyword (and use a plain Click at its call sites) once that issue is closed.
  [Arguments]  ${selector}  ${retries}=2
  ${retries} =  Convert To Integer  ${retries}
  FOR  ${attempt}  IN RANGE  0  ${retries + 1}
    Click  ${selector}
    Sleep  200ms
    ${csrf_error_visible} =  Get Element Count  //*[contains(.,'CSRF')]
    IF  ${csrf_error_visible} == 0  BREAK
    Log
    ...  message=CSRF error found after click ${attempt + 1} of ${retries + 1}. See https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    ...  level=WARN
  END
  IF  ${csrf_error_visible} > 0
    Fail  CSRF error still present after ${retries} retries clicking ${selector}. See https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
  END

Input Errors Should Be Present
  Get Element Count  //*[contains(@class,"js-input-errors")]  greater than  0

Success Alert Is Visible
  [Arguments]
  ...  ${contains_message}=${EMPTY}
  ...  ${selector}=//*[@data-e2e-name="alert"][contains(@class, 'bhr-alert--success')]
  Wait For Elements State  ${selector}  attached
  IF  "${contains_message}" != '${EMPTY}'
    Get Text  ${selector}  contains  ${contains_message}
  END

Verify Image
  [Arguments]  ${selector}
  Wait For Elements State  ${selector}  visible  2min

Upload And Submit
  [Arguments]  ${file_name}  ${advice_location}
  Upload File By Selector  //dialog[@open]//*[@data-e2e-name="uploadUuid-input"]  ${advice_location}
  Wait For Condition  Text  //dialog[@open]//*[@data-e2e-name="uploaded-file"]  contains  ${file_name}
  Type Text  //dialog[@open]//*[@data-e2e-name="formalDate-input"]  01012022
  Click  //dialog[@open]//*[@data-e2e-name="submit"]
  Get Element Count  //dialog[@open]//*[@data-e2e-name="save-failed"]  should be  0
