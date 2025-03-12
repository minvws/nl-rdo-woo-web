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


*** Variables ***
${CURRENT_TIME}             ${EMPTY}
${CURRENT_DATE}             ${EMPTY}
${CURRENT_DATE_FORMAT2}     ${EMPTY}
${CURRENT_EPOCH}            ${EMPTY}
${RUN_LOCALLY}              ${FALSE}
${HEADLESS}                 ${EMPTY}


*** Keywords ***
Suite Setup - CI
  [Documentation]  Creates an admin user if there isn't any, then logs in to the admin interface.
  # If run locally, set the OTP code from the local env vars coz the setup has likely already ran
  IF  ${RUN_LOCALLY}
    VAR  ${TST_BALIE_OTP_SECRET}  %{SECRET_WOO_LOCAL}  scope=global
  END
  # On first run of this keyword, create the user.
  IF  '${TST_BALIE_OTP_SECRET}' == '${EMPTY}'
    ${temp_password} =  Create Woo Admin User
    Open Browser And BaseUrl
    Login Admin  password=${temp_password}
  ELSE
    Open Browser And BaseUrl
  END
  Close Symfony Dev Bar
  Setting Global Variables

Cleansheet
  [Documentation]  In some tests we want unique prefixes, which must also be cleaned up afterwards. Also allows for removing users. Always keeps subjects.
  [Arguments]  ${keep_prefixes}=${TRUE}  ${include_users}=${FALSE}
  Run Process  task worker:stop  shell=True
  VAR  ${args}  -- --keep-subjects
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

Setting Global Variables
  ${time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${date} =  Get Current Date  result_format=%Y-%m-%d
  ${timestamp} =  Get Utc Timestamp
  ${date2} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${epoch} =  Get Time  format=epoch
  VAR  ${CURRENT_TIME}  ${time}  scope=global
  VAR  ${CURRENT_DATE}  ${date}  scope=global
  VAR  ${CURRENT_DATE_FORMAT2}  ${date2}  scope=global
  VAR  ${CURRENT_EPOCH}  ${epoch}  scope=global

Open Browser And BaseUrl
  [Arguments]  ${device}=Desktop Chrome  ${slow_mo}=50ms
  ${device_context} =  Get Device  ${device}
  IF  'Desktop' in '${device}' and not ${RUN_LOCALLY}
    VAR  ${viewport} =  { "width": 1920, "height": 970 }  # this is the viewport when your resolution is 1920x1080
  ELSE
    VAR  ${viewport} =  ${device_context}[viewport]
  END
  New Browser
  ...  browser=${device_context}[defaultBrowserType]
  ...  headless=${HEADLESS}
  ...  args=["--ignore-certificate-errors", "--lang=nl"]
  ...  slowMo=${slow_mo}
  New Context  &{device_context}  locale=nl-NL  viewport=${viewport}
  New Page  ${BASE_URL}

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
  VAR  ${TST_BALIE_OTP_SECRET}  ${otp_secret}  scope=global
  RETURN  ${password}

Close Symfony Dev Bar
  ${c} =  Get Element Count  //body/div/div[3]/button
  IF  ${c}>0  Click  //body/div/div[3]/button

Clear Local Secret
  ${command} =  Set Variable  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=/g' ~/.zshrc
  Run Process  ${command}  shell=True  alias=clear_secret
  ${stdout}  ${stderr} =  Get Process Result  clear_secret  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error clearing local secret: ${stdout}
