*** Settings ***
Documentation       Resource file with generic keywords.
Library             Process
Library             String
Library             OperatingSystem
Library             Browser
Library             OTP
Resource            Admin.resource
Resource            DecisionDossiers.resource


*** Variables ***
${RUN_LOCALLY}  ${FALSE}
${BASE_URL}     ${EMPTY}
${HEADLESS}     ${EMPTY}
${WW}           ${EMPTY}
${OTP_CODE}     ${EMPTY}


*** Keywords ***
CI Suite Setup
  IF  ${RUN_LOCALLY}
    Cleanup Script
    Set Suite Variable  ${OTP_CODE}  %{SECRET_WOO_LOCAL}
  ELSE
    Create Woo Admin User
    First Time Login With Admin
  END
  Open Browser And BaseUrl
  Close Symfony Dev Bar
  Setting Suite Variables
  Login Balie
  Create New Decision Dossier  upload_files=${TRUE}  env=ci

Test Suite Setup
  Open Browser And BaseUrl
  Setting Suite Variables

Cleanup Script
  ${cleanup_db} =  Set Variable  docker-compose exec app bin/console woopie:dev:clean-sheet --force
  Run Process  ${cleanup_db}  shell=True  alias=cleanup

Create Woo Admin User
  ${make_user_command} =  Set Variable
  ...  php bin/console woopie:user:create "${TST_BALIE_USER}" "full name" --super-admin
  Run Process  ${make_user_command}  shell=True  alias=create_admin
  ${stdout}  ${stderr} =  Get Process Result  create_admin  stdout=True  stderr=True
  Should Be Empty  ${stderr}  Error creating admin ${stderr}
  ${regel_ww} =  Get Line  ${stdout}  1
  ${ww} =  Get Substring  ${regel_ww}  13
  ${otp_line} =  Get Line  ${stdout}  3
  ${otp_code} =  Get Substring  ${otp_line}  13
  # Should Not Be Empty  ${otp_code}  No otp code found in: ${stdout}
  Set Suite Variable  ${OTP_CODE}  ${otp_code}
  Set Suite Variable  ${WW}  ${ww}

Open Browser And BaseUrl
  ${device_context} =  Get Device  Desktop Chrome
  New Browser
  ...  browser=${device_context}[defaultBrowserType]
  ...  headless=${HEADLESS}
  ...  args=["--ignore-certificate-errors", "--lang=nl"]
  New Context  &{device_context}  locale=nl-NL  acceptDownloads=True
  New Page  ${BASE_URL}

Setting Suite Variables
  ${yyyy}  ${mm}  ${dd} =  Get Time  year,month,day
  ${current_time} =  Get Time  format=%Y-%m-%d %H:%M:%S
  ${current_date} =  Catenate  SEPARATOR=-  ${yyyy}  ${mm}  ${dd}
  ${current_epoch} =  Get Time  format=epoch
  Set Suite Variable  $CURRENT_TIME  ${current_time}
  Set Suite Variable  $CURRENT_DATE  ${current_date}
  Set Suite Variable  $CURRENT_EPOCH  ${current_epoch}

First Time Login With Admin
  Open Browser And BaseUrl
  Login Balie  ${TST_BALIE_USER}  ${WW}
  Go To  ${BASE_URL}/balie/login
  Fill Text  id=change_password_current_password  ${WW}
  Fill Text  id=change_password_plainPassword_first  ${TST_BALIE_PASSWORD}
  Fill Text  id=change_password_plainPassword_second  ${TST_BALIE_PASSWORD}
  Click  " Wachtwoord aanpassen "
  Get Text  //body  *=  Uitloggen
  Logout Balie
  Close Page  CURRENT

Close Symfony Dev Bar
  Click  //body/div/div[3]/button
