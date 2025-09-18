*** Settings ***
Documentation       This file contains testcases that can be used to initiate a new environment and getting an OTP.
Resource            ../resources/Setup.resource
Test Tags           ci


*** Test Cases ***
Create Admin User And Do First Login
  [Documentation]  Creates a new admin user and stores the OTP code in your ~/.zshrc file.
  ...  Executed both in CI as first test, and locally after preparing your env.
  [Tags]  init
  ${temp_password} =  Create Woo Admin User
  Write Otp Secret To Zshrc  ${TST_BALIE_OTP_SECRET}
  Set Environment Variable  ADMIN_OTP_SECRET  ${TST_BALIE_OTP_SECRET}
  Set URL Variables
  Open Browser And BaseUrl
  Login Admin
  ...  username=${TST_BALIE_USER}
  ...  password=${temp_password}
  ...  otp_secret=${TST_BALIE_OTP_SECRET}
  ...  new_password=${TST_BALIE_PASSWORD}

Cleansheet
  [Tags]  cleansheet
  Cleansheet


*** Keywords ***
Write Otp Secret To Zshrc
  [Arguments]  ${otp_secret}
  VAR  ${command} =  sed -ri '' 's/SECRET_WOO_LOCAL=[0-9A-Z]*/SECRET_WOO_LOCAL=${otp_secret}/g' ~/.zshrc
  Run Process  ${command}  shell=True
  Log  \nSECRET_WOO_LOCAL: ${otp_secret}
  Run Process  source ~/.zshrc  shell=True
