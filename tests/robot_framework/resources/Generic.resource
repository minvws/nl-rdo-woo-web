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
${TST_BALIE_USER}           email@example.org
${TST_BALIE_PASSWORD}       IkLoopNooitVastVandaag
${TST_BALIE_OTP_SECRET}     ${EMPTY}
${DOSSIER_REFERENCE}        ${EMPTY}


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
  ${url} =  Get Url
  IF  'localhost' in '${url}' # Locally we get a detailed Symfony error page
    Get Text  //h2[@class="exception-http"]  contains  ${error_code}
  ELSE IF  '${error_code}' == '404'
    Get Text  //h1  contains  Pagina niet gevonden
  ELSE IF  '${error_code}' == '403'
    Get Text  //h1  contains  Log in alstublieft
  ELSE
    Fatal Error  Not supported yet
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
  [Arguments]  ${selector}
  Click  ${selector}
  ${csfr_error_visible} =  Get Element Count  //*[contains(.,'CSRF')]
  IF  ${csfr_error_visible} > 0
    # Retry on CSRF error, another instance of https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Log  message=CSRF error found, retrying the click.  level=WARN
    Click  ${selector}
  END

Go To With Response
  [Arguments]  ${url}
  Go To  ${url}
  ${response} =  Wait For Response  ${url}
  ${status} =  Get From Dictionary  ${response}  status
  RETURN  ${status}

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
