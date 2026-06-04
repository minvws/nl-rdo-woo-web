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
${OTP_SECRET_PATH}          ${EMPTY}
${ADMIN_CONTAINER_NAME}     nl-rdo-woo-web-private-admin-1
${ENVIRONMENT}              ${EMPTY}
&{TENANT_CONFIGS}           &{EMPTY}
${URL_ADMIN}                ${EMPTY}
${URL_PUBLIC}               ${EMPTY}
${URL_API}                  ${EMPTY}
${CLIENT_CERT}              ${EMPTY}
${CLIENT_KEY}               ${EMPTY}
${CA_BUNDLE}                ${EMPTY}


*** Keywords ***
Suite Setup Generic
  [Documentation]  Opens a new browser and sets environment specific credential variables.
  Set DateTime Variables
  Set Tenant Context  %{TENANT}
  IF  '${ENVIRONMENT}' == 'local'
    Open Browser And BaseUrl
    ${admin_otp_secret} =  Get OTP Secret
    Set Credential Variables  ${TST_BALIE_USER}  ${TST_BALIE_PASSWORD}  ${admin_otp_secret}
  ELSE IF  '${ENVIRONMENT}' == 'test' or '${ENVIRONMENT}' == 'acc'
    ${config} =  Get From Dictionary  ${TENANT_CONFIGS}  %{TENANT}
    Open Browser And BaseUrl  http_username=${config}[HTACCESS_USERNAME]  http_password=${config}[HTACCESS_PASSWORD]
    Set Credential Variables  ${config}[ADMIN_EMAIL]  ${config}[ADMIN_PASSWORD]  ${config}[ADMIN_SECRET]
  ELSE
    Fatal Error  Env var ENVIRONMENT returned unsupported value '${ENVIRONMENT}'.
  END
  Set Browser Timeout  1 min

Set Credential Variables
  [Arguments]  ${username}  ${password}  ${otp_secret}
  VAR  ${ADMIN_USER} =  ${username}  scope=suite
  VAR  ${ADMIN_PASSWORD} =  ${password}  scope=suite
  VAR  ${ADMIN_OTP_SECRET} =  ${otp_secret}  scope=suite

Set DateTime Variables
  ${time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${date} =  Get Current Date  result_format=%Y-%m-%d
  ${timestamp} =  Get Utc Timestamp
  ${date2} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${epoch} =  Get Time  format=epoch
  VAR  ${CURRENT_TIME} =  ${time}  scope=suite
  VAR  ${CURRENT_DATE} =  ${date}  scope=suite
  VAR  ${CURRENT_DATE_FORMAT2} =  ${date2}  scope=suite
  VAR  ${CURRENT_EPOCH} =  ${epoch}  scope=suite

Open Browser And BaseUrl
  [Arguments]  ${slow_mo}=50ms  ${http_username}=${EMPTY}  ${http_password}=${EMPTY}
  VAR  ${device} =  %{DEVICE=Desktop Chrome}
  ${device_context} =  Get Device  ${device}
  IF  'Desktop' in '${device}' and '${ENVIRONMENT}' != 'local'
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

Set Tenant Context
  [Documentation]  Sets all tenant-specific Robot Framework variables from the TENANT_CONFIGS dictionary.
  [Arguments]  ${tenant}
  ${config} =  Get From Dictionary  ${TENANT_CONFIGS}  ${tenant}
  ${env} =  Get Environment Variable  ENVIRONMENT  local
  VAR  ${ENVIRONMENT} =  ${env}  scope=suite
  VAR  ${TENANT} =  ${tenant}  scope=suite
  VAR  ${URL_ADMIN} =  ${config}[URL_ADMIN]  scope=suite
  VAR  ${URL_PUBLIC} =  ${config}[URL_PUBLIC]  scope=suite
  VAR  ${URL_API} =  ${config}[URL_API]  scope=suite
  VAR  ${CLIENT_CERT} =  ${config}[CLIENT_CERT]  scope=suite
  VAR  ${CLIENT_KEY} =  ${config}[CLIENT_KEY]  scope=suite
  VAR  ${CA_BUNDLE} =  ${config}[CA_BUNDLE]  scope=suite
  VAR  ${OTP_SECRET_PATH} =  secrets/.otp_secret_${TENANT}  scope=suite
