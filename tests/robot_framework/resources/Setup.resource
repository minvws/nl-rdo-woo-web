*** Comments ***
# robocop: off=no-global-variable,unused-variable


*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             OperatingSystem
Library             Process
Library             String
Library             Browser
Library             DateTimeTZ
Library             DependencyLibrary
Library             OTP
Resource            Admin.resource
Resource            Public.resource


*** Variables ***
${OTP_SECRET_PATH}          secrets/.otp_secret
${ADMIN_CONTAINER_NAME}     nl-rdo-woo-web-private-admin-1


*** Keywords ***
Suite Setup Generic
  [Documentation]  Opens a new browser and sets environment specific credential variables.
  Set DateTime Variables
  IF  '%{ENVIRONMENT}' == 'local'
    Open Browser And BaseUrl
    ${admin_otp_secret} =  Get OTP Secret
    Set Credential Variables  ${TST_BALIE_USER}  ${TST_BALIE_PASSWORD}  ${admin_otp_secret}
  ELSE IF  '%{ENVIRONMENT}' == 'test' or '%{ENVIRONMENT}' == 'acc'
    Open Browser And BaseUrl  http_username=%{HTACCESS_USERNAME}  http_password=%{HTACCESS_PASSWORD}
    Set Credential Variables  %{ADMIN_EMAIL}  %{ADMIN_PASSWORD}  %{ADMIN_SECRET}
  ELSE
    Fatal Error  Env var ENVIRONMENT returned unsupported value '%{ENVIRONMENT}'.
  END
  Set Browser Timeout  1 min

Set Credential Variables
  [Arguments]  ${username}  ${password}  ${otp_secret}
  VAR  ${ADMIN_USER} =  ${username}  scope=global
  VAR  ${ADMIN_PASSWORD} =  ${password}  scope=global
  VAR  ${ADMIN_OTP_SECRET} =  ${otp_secret}  scope=global

Set DateTime Variables
  ${time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${date} =  Get Current Date  result_format=%Y-%m-%d
  ${timestamp} =  Get Utc Timestamp
  ${date2} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${epoch} =  Get Time  format=epoch
  VAR  ${CURRENT_TIME} =  ${time}  scope=global
  VAR  ${CURRENT_DATE} =  ${date}  scope=global
  VAR  ${CURRENT_DATE_FORMAT2} =  ${date2}  scope=global
  VAR  ${CURRENT_EPOCH} =  ${epoch}  scope=global

Open Browser And BaseUrl
  [Arguments]  ${slow_mo}=50ms  ${http_username}=${EMPTY}  ${http_password}=${EMPTY}
  VAR  ${device} =  %{DEVICE=Desktop Chrome}
  ${device_context} =  Get Device  ${device}
  IF  'Desktop' in '${device}' and '%{ENVIRONMENT}' != 'local'
    # When not running locally on potentially a laptop screen, we resize the viewport so it fits within a 1920x1080 resolution.
    VAR  ${viewport} =  { "width": 1920, "height": 970 }
  ELSE
    VAR  ${viewport} =  ${device_context}[viewport]
  END
  New Browser
  ...  browser=${device_context}[defaultBrowserType]
  ...  headless=%{HEADLESS=false}
  ...  args=["--ignore-certificate-errors", "--lang=nl", "--disable-dev-shm-usage", "--no-sandbox"]
  ...  slowMo=${slow_mo}
  IF  '${http_username}' != '${EMPTY}'
    VAR  ${username} =  ${http_username}
    VAR  ${password} =  ${http_password}
    New Context
    ...  &{device_context}
    ...  locale=nl-NL
    ...  viewport=${viewport}
    ...  httpCredentials={'username': '$username', 'password': '$password'}
  ELSE
    New Context  &{device_context}  locale=nl-NL  viewport=${viewport}
  END
  New Page
  Go To Public

Get OTP Secret
  ${otp_secret} =  Get File  ${OTP_SECRET_PATH}
  RETURN  ${otp_secret}
