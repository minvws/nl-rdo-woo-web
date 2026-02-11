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
${HEADLESS}                 ${EMPTY}
${OTP_SECRET_PATH}          .otp_secret
${ADMIN_CONTAINER_NAME}     nl-rdo-woo-web-private-admin-1


*** Keywords ***
Suite Setup Generic
  [Documentation]  Creates an admin user if there isn't any, then logs in to the admin interface.
  Set DateTime Variables
  IF  '%{ENVIRONMENT}' == 'local' or '%{ENVIRONMENT}' == 'ci' or '%{ENVIRONMENT}' == 'docker'
    Open Browser And BaseUrl
    ${admin_otp_secret} =  Get OTP Secret
    Set Credential Variables  ${TST_BALIE_USER}  ${TST_BALIE_PASSWORD}  ${admin_otp_secret}
  ELSE IF  '%{ENVIRONMENT}' == 'test'
    Open Browser And BaseUrl  http_username=%{USERNAME_WOO_TEST}  http_password=%{PASSWORD_WOO_TEST}
    Set Credential Variables  %{EMAIL_WOO_TEST_BALIE}  %{PASSWORD_WOO_TEST_BALIE}  %{SECRET_WOO_TEST_BALIE}
  ELSE IF  '%{ENVIRONMENT}' == 'acc'
    Open Browser And BaseUrl  http_username=%{USERNAME_WOO_STAGING}  http_password=%{PASSWORD_WOO_STAGING}
    Set Credential Variables  %{EMAIL_WOO_STAGING_BALIE}  %{PASSWORD_WOO_STAGING_BALIE}  %{SECRET_WOO_STAGING_BALIE}
  ELSE
    Fatal Error  Env var ENVIRONMENT returned unsupported value '%{ENVIRONMENT}'.
  END
  Set Browser Timeout  1 min

Set Credential Variables
  [Arguments]  ${username}  ${password}  ${otp_secret}
  VAR  ${ADMIN_USER} =  ${username}  scope=global
  VAR  ${ADMIN_PASSWORD} =  ${password}  scope=global
  VAR  ${ADMIN_OTP_SECRET} =  ${otp_secret}  scope=global

Cleansheet
  [Documentation]  In some tests we want unique prefixes, which must also be cleaned up afterwards. Also allows for removing users. Always keeps subjects.
  [Arguments]  ${keep_prefixes}=${TRUE}  ${include_users}=${FALSE}
  Run Process  task worker:stop  shell=True
  VAR  ${args} =  -- --keep-subjects
  IF  ${keep_prefixes}
    ${args} =  Catenate  ${args}  --keep-prefixes
  END
  IF  ${include_users}
    ${args} =  Catenate  ${args}  --users
    Clear OTP Secret
  END
  Run Process  task rf:cleansheet ${args}  shell=True  alias=cleansheet
  ${result} =  Get Process Result  cleansheet
  Should Not Contain  ${result.stdout}  exception
  Run Process  task worker:start  shell=True

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

Create Woo Admin User
  [Documentation]  Creates a super admin user using the CLI, that can be used for all robot tests. Returns the temporary password.
  VAR  ${make_user_command} =
  ...  docker exec ${ADMIN_CONTAINER_NAME} bin/console woopie:user:create "${TST_BALIE_USER}" "Robot Admin" --super-admin
  Run Process  ${make_user_command}  shell=True  alias=create_admin
  ${stdout}  ${stderr} =  Get Process Result  create_admin  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error creating admin user: ${stdout}
  ${password}  ${otp_secret} =  Get Password And OTP Secret From Console Output  ${stdout}
  VAR  ${TST_BALIE_OTP_SECRET} =  ${otp_secret}  scope=global
  RETURN  ${password}

Get Password And OTP Secret From Console Output
  [Arguments]  ${console_output}
  ${regel_password} =  Get Line  ${console_output}  1
  ${password} =  Get Substring  ${regel_password}  13
  ${otp_line} =  Get Line  ${console_output}  3
  ${otp_secret} =  Get Substring  ${otp_line}  13
  RETURN  ${password}  ${otp_secret}

Store OTP Secret
  [Arguments]  ${otp_secret}
  Create File  ${OTP_SECRET_PATH}  ${otp_secret}

Get OTP Secret
  ${otp_secret} =  Get File  ${OTP_SECRET_PATH}
  RETURN  ${otp_secret}

Clear OTP Secret
  Remove File  ${OTP_SECRET_PATH}
