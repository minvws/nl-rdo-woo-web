*** Comments ***
# robocop: off=no-global-variable


*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             OperatingSystem
Library             Process
Library             String
Library             DateTimeTZ
Library             Browser
Library             OTP
Resource            Admin.resource
Resource            Public.resource

*** Variables ***
${ENVIRONMENT}  ${EMPTY}
${HEADLESS}     ${EMPTY}


*** Keywords ***
Suite Setup Generic
  [Documentation]  Creates an admin user if there isn't any, then logs in to the admin interface.
  Set DateTime Variables
  Set URL Variables
  IF  '${ENVIRONMENT}' == 'docker-local'
    # If run locally, the user should have been created manually (still) using the tag=init, so we set the OTP code from the local env var
    Open Browser And BaseUrl
    Set Credential Variables  ${TST_BALIE_USER}  ${TST_BALIE_PASSWORD}  %{SECRET_WOO_LOCAL}
  ELSE IF  '${ENVIRONMENT}' == 'docker-ci'
    # On first run of this keyword, create a user
    IF  '${ADMIN_OTP_SECRET}' == '${EMPTY}'
      ${temp_password} =  Create Woo Admin User
      Open Browser And BaseUrl
      # Log in once to change the temp password
      ${new_password} =  Login Admin
      ...  username=${TST_BALIE_USER}
      ...  password=${temp_password}
      ...  otp_secret=${TST_BALIE_OTP_SECRET}
      Set Credential Variables  ${TST_BALIE_USER}  ${new_password}  ${TST_BALIE_OTP_SECRET}
    ELSE
      Open Browser And BaseUrl
    END
  ELSE IF  '${ENVIRONMENT}' == 'test'
    Open Browser And BaseUrl  http_username=%{USERNAME_WOO_TEST}  http_password=%{PASSWORD_WOO_TEST}
    Set Credential Variables  %{EMAIL_WOO_TEST_BALIE}  %{PASSWORD_WOO_TEST_BALIE}  %{SECRET_WOO_TEST_BALIE}
  ELSE IF  '${ENVIRONMENT}' == 'acc'
    Open Browser And BaseUrl  http_username=%{USERNAME_WOO_STAGING}  http_password=%{PASSWORD_WOO_STAGING}
    Set Credential Variables  %{EMAIL_WOO_STAGING_BALIE}  %{PASSWORD_WOO_STAGING_BALIE}  %{SECRET_WOO_STAGING_BALIE}
  END
  Close Symfony Dev Bar

Set URL Variables
  VAR  ${URL_PUBLIC} =  http://%{URL_PUBLIC}  scope=global
  VAR  ${URL_ADMIN} =  http://%{URL_ADMIN}  scope=global

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
    Clear Local Secret
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
  IF  'Desktop' in '${device}' and '${ENVIRONMENT}' != 'docker-local'
    # When not running locally on potentially a laptop screen, we resize the viewport so it fits within a 1920x1080 resolution.
    VAR  ${viewport} =  { "width": 1920, "height": 970 }
  ELSE
    VAR  ${viewport} =  ${device_context}[viewport]
  END
  New Browser
  ...  browser=${device_context}[defaultBrowserType]
  ...  headless=%{HEADLESS=false}
  ...  args=["--ignore-certificate-errors", "--lang=nl"]
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
  Set Browser Timeout  20s
  Go To Public

Create Woo Admin User
  [Documentation]  Creates a super admin user using the CLI, that can be used for all robot tests. Returns the temporary password.
  ${make_user_command} =  Set Variable
  ...  task app:user:create -- "${TST_BALIE_USER}" "Robot Admin" --super-admin
  Run Process  ${make_user_command}  shell=True  alias=create_admin
  ${stdout}  ${stderr} =  Get Process Result  create_admin  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error creating admin user: ${stdout}
  ${regel_password} =  Get Line  ${stdout}  1
  ${password} =  Get Substring  ${regel_password}  13
  ${otp_line} =  Get Line  ${stdout}  3
  ${otp_secret} =  Get Substring  ${otp_line}  13
  VAR  ${TST_BALIE_OTP_SECRET} =  ${otp_secret}  scope=global
  RETURN  ${password}

Close Symfony Dev Bar
  ${c} =  Get Element Count  //body/div/div[3]/button
  IF  ${c}>0  Click  //body/div/div[3]/button

Clear Local Secret
  ${command} =  Set Variable  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=/g' ~/.zshrc
  Run Process  ${command}  shell=True  alias=clear_secret
  ${stdout}  ${stderr} =  Get Process Result  clear_secret  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error clearing local secret: ${stdout}

Load VWS Fixtures
  # This fails because the VWS department is already loaded in https://github.com/minvws/nl-rdo-woo-web-private/blob/273f83b6160bc29d73ac95a5f2d1523cff65cbd3/migrations/Version20230929070108.php#L25, thus creating a unique constraint error.
  # Fixing this is nasty, so for now we choose to disable tests that require this.
  Run Process  task rf:fixtures:load:vws  shell=True  alias=shell
  ${result} =  Get Process Result  shell
  Should Be Empty  ${result.stderr}

Load E2E Fixtures
  Run Process  task rf:fixtures:load:e2e  shell=True  alias=shell
  ${result} =  Get Process Result  shell
  Should Be Empty  ${result.stderr}
