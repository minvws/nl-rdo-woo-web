*** Settings ***
Documentation       This file contains testcases that can be used to initiate a new environment and getting an OTP.
Resource            ../resources/Setup.resource


*** Test Cases ***
Initiate local environment
  [Documentation]  Creates a new admin user and stores the OTP code in your .zshrc file.
  [Tags]  init
  ${temp_password} =  Create Woo Admin User
  Write Otp Secret To Zshrc  ${TST_BALIE_OTP_SECRET}
  Open Browser And BaseUrl
  Login Admin  ${TST_BALIE_USER}  ${temp_password}  new_password=${TST_BALIE_PASSWORD}

Get Local OTP Code
  [Documentation]  Generate OTP code using local secret
  [Tags]  otp
  ${otp} =  Get Otp  %{SECRET_WOO_LOCAL}
  Log To Console  OTP code: ${otp}

Login
  [Tags]  login
  Suite Setup - CI
  Login Admin


*** Keywords ***
Write Otp Secret To Zshrc
  [Arguments]  ${otp_secret}
  ${command} =  Set Variable
  ...  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=${otp_secret}/g' ~/.zshrc
  Run Process  ${command}  shell=True
  Log To Console  \nSECRET_WOO_LOCAL: ${otp_secret}
  Run Process  source ~/.zshrc  shell=True
