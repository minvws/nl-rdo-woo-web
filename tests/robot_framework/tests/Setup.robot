*** Settings ***
Resource    ../resources/Setup.resource


*** Variables ***
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag


*** Test Cases ***
Initiate local environment
  [Documentation]  Creates a new admin user and stores the OTP code in your .zshrc file.
  [Tags]  init
  Create Woo Admin User
  First Time Login With Admin
  # Write OTP_CODE to zshrc
  ${command} =  Set Variable  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=${OTP_CODE}/g' ~/.zshrc
  Run Process  ${command}  shell=True
  Log To Console  \nSECRET_WOO_LOCAL: ${OTP_CODE}
  Run Process  source ~/.zshrc  shell=True

Get Local OTP Code
  [Documentation]  Generate OTP code using local secret
  [Tags]  otp
  ${otp} =  Get Otp  %{SECRET_WOO_LOCAL}
  Log To Console  OTP code: ${otp}

Login
  [Tags]  login
  Set Global Variable  ${OTP_CODE}  %{SECRET_WOO_LOCAL}
  Open Browser And BaseUrl
  Login Admin
  Debug
