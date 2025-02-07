*** Settings ***
Resource    ../resources/Setup.resource


*** Variables ***
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag
${TEMP_WW}              ${EMPTY}


*** Test Cases ***
Initiate local environment
  [Documentation]  Creates a new admin user and stores the OTP code in your .zshrc file.
  [Tags]  init
  Create Woo Admin User
  Open Browser And BaseUrl
  Login Admin  ${TST_BALIE_USER}  ${TEMP_WW}  new_password=${TST_BALIE_PASSWORD}
  # Write OTP_CODE to zshrc
  ${command} =  Set Variable
  ...  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=${TST_BALIE_OTP_SECRET}/g' ~/.zshrc
  Run Process  ${command}  shell=True
  Log To Console  \nSECRET_WOO_LOCAL: ${TST_BALIE_OTP_SECRET}
  Run Process  source ~/.zshrc  shell=True

Get Local OTP Code
  [Documentation]  Generate OTP code using local secret
  [Tags]  otp
  ${otp} =  Get Otp  %{SECRET_WOO_LOCAL}
  Log To Console  OTP code: ${otp}

Login
  [Tags]  login
  VAR  ${TST_BALIE_OTP_SECRET}  %{SECRET_WOO_LOCAL}  scope=global
  Open Browser And BaseUrl
  Login Admin
