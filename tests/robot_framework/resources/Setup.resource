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
${RUN_LOCALLY}          ${FALSE}
${HEADLESS}             ${EMPTY}
${WW}                   ${EMPTY}
${OTP_CODE}             ${EMPTY}
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie/dossiers
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag


*** Keywords ***
Suite Setup - CI
  [Arguments]  ${cleansheet}=${TRUE}  ${include_prefixes}=${FALSE}
  IF  ${cleansheet}  Cleansheet  ${include_prefixes}
  # If run locally, set the OTP code from the local env vars coz the setup has likely already ran
  IF  ${RUN_LOCALLY}  VAR  ${OTP_CODE}  %{SECRET_WOO_LOCAL}  scope=global
  # On first run of this keyword, create the user.
  IF  '${OTP_CODE}' == '${EMPTY}'
    Create Woo Admin User
    First Time Login With Admin
  END
  Open Browser And BaseUrl
  Close Symfony Dev Bar
  Setting Global Variables

Cleansheet
  [Documentation]  In some tests we want unique prefixes, which must also be cleaned up afterwards.
  [Arguments]  ${include_prefixes}=${FALSE}
  Run Process  task worker:stop  shell=True
  IF  ${include_prefixes}
    Run Process  task app:cleansheet -- -p  shell=True  alias=cleansheet
    ${result} =  Get Process Result  cleansheet
    Should Not Contain  ${result.stdout}  exception
  ELSE
    Run Process  task app:cleansheet  shell=True  alias=cleansheet
    ${result} =  Get Process Result  cleansheet
    Should Not Contain  ${result.stdout}  exception
  END
  Run Process  task worker:start  shell=True

Setting Global Variables
  ${current_time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${current_date} =  Get Current Date  result_format=%Y-%m-%d
  ${timestamp} =  Get Utc Timestamp
  ${current_date_format2} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${current_epoch} =  Get Time  format=epoch
  VAR  ${current_time}  ${current_time}  scope=global
  VAR  ${current_date}  ${current_date}  scope=global
  VAR  ${current_date_format2}  ${current_date_format2}  scope=global
  VAR  ${current_epoch}  ${current_epoch}  scope=global

Open Browser And BaseUrl
  [Arguments]  ${device}=Desktop Chrome  ${slow_mo}=50ms
  ${device_context} =  Get Device  ${device}
  New Browser
  ...  browser=${device_context}[defaultBrowserType]
  ...  headless=${HEADLESS}
  ...  args=["--ignore-certificate-errors", "--lang=nl"]
  ...  slowMo=${slow_mo}
  New Context  &{device_context}  locale=nl-NL  acceptDownloads=True
  New Page  ${BASE_URL}

Create Woo Admin User
  ${make_user_command} =  Set Variable
  ...  task app:user:create -- "${TST_BALIE_USER}" "full name" --super-admin
  Run Process  ${make_user_command}  shell=True  alias=create_admin
  ${stdout}  ${stderr} =  Get Process Result  create_admin  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error creating admin ${stderr}
  ${regel_ww} =  Get Line  ${stdout}  1
  ${ww} =  Get Substring  ${regel_ww}  13
  ${otp_line} =  Get Line  ${stdout}  3
  ${otp_code} =  Get Substring  ${otp_line}  13
  # Should Not Be Empty  ${otp_code}  No otp code found in: ${stdout}
  VAR  ${otp_code}  ${otp_code}  scope=global
  VAR  ${ww}  ${ww}  scope=global

First Time Login With Admin
  Open Browser And BaseUrl
  Login Admin  ${TST_BALIE_USER}  ${WW}
  Go To  ${BASE_URL}/balie/login
  Fill Text  id=change_password_current_password  ${WW}
  Fill Text  id=change_password_plainPassword_first  ${TST_BALIE_PASSWORD}
  Fill Text  id=change_password_plainPassword_second  ${TST_BALIE_PASSWORD}
  Click  " Wachtwoord aanpassen "
  Get Text  //body  *=  Uitloggen
  Logout Admin
  Close Page  CURRENT

Close Symfony Dev Bar
  ${c} =  Get Element Count  //body/div/div[3]/button
  IF  ${c}>0  Click  //body/div/div[3]/button
