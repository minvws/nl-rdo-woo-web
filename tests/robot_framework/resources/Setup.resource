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
${RUN_LOCALLY}  ${FALSE}
${BASE_URL}     ${EMPTY}
${HEADLESS}     ${EMPTY}
${WW}           ${EMPTY}
${OTP_CODE}     ${EMPTY}


*** Keywords ***
Suite Setup - CI
  IF  ${RUN_LOCALLY}
    Cleansheet
    Set Global Variable  ${OTP_CODE}  %{SECRET_WOO_LOCAL}
  ELSE IF  '${OTP_CODE}' == '${EMPTY}'
    Create Woo Admin User
    First Time Login With Admin
  END
  Open Browser And BaseUrl
  Close Symfony Dev Bar
  Setting Global Variables

Suite Setup - Test
  Open Browser And BaseUrl
  Setting Global Variables

Cleansheet
  Run Process  task worker:stop  shell=True
  Run Process  task app:cleansheet  shell=True
  Run Process  task worker:start  shell=True

Setting Global Variables
  ${current_time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${current_date} =  Get Current Date  result_format=%Y-%m-%d
  ${timestamp} =  Get Utc Timestamp  locale=nl  time_format=dd LLL y H:mm:ss
  ${current_date_format2} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${current_epoch} =  Get Time  format=epoch
  Set Global Variable  $CURRENT_TIME  ${current_time}
  Set Global Variable  $CURRENT_DATE  ${current_date}
  Set Global Variable  $CURRENT_DATE_FORMAT2  ${current_date_format2}
  Set Global Variable  $CURRENT_EPOCH  ${current_epoch}

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
  Set Global Variable  ${OTP_CODE}  ${otp_code}
  Set Global Variable  ${WW}  ${ww}

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
  Click  //body/div/div[3]/button
