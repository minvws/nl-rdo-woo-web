*** Settings ***
Documentation       This testcase is used to initialize a new environment.
Resource            ../../resources/Setup.resource
Test Tags           ci


*** Test Cases ***
Create Admin User And Do First Login
  [Documentation]  Creates a new admin user and stores the OTP code in your ~/.zshrc file.
  ...  Executed both in CI as first test, and locally after preparing your env.
  [Tags]  init
  ${temp_password} =  Create Woo Admin User
  Store OTP Secret  ${TST_BALIE_OTP_SECRET}
  Set Environment Variable  ADMIN_OTP_SECRET  ${TST_BALIE_OTP_SECRET}
  Set URL Variables
  Open Browser And BaseUrl
  Login Admin
  ...  username=${TST_BALIE_USER}
  ...  password=${temp_password}
  ...  otp_secret=${TST_BALIE_OTP_SECRET}
  ...  new_password=${TST_BALIE_PASSWORD}
