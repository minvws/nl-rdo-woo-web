*** Settings ***
Documentation       This testcase is used to initialize a new environment.
Resource            ../../resources/Setup.resource
Test Tags           ci


*** Test Cases ***
Create Admin User And Do First Login
  [Documentation]  Creates a new admin user and stores the OTP code in your ~/.zshrc file.
  ...  Executed both in CI as first test, and locally after preparing your env.
  [Tags]  init
  ${already_exists} =  Check If Admin User Exists
  IF  ${already_exists}
    Log  Admin user already exists, skipping create  level=WARN
  ELSE
    ${temp_password} =  Create Woo Admin User
    Store OTP Secret  ${TST_BALIE_OTP_SECRET}
    Set Environment Variable  ADMIN_OTP_SECRET  ${TST_BALIE_OTP_SECRET}
    Open Browser And BaseUrl
    Login Admin
    ...  username=${TST_BALIE_USER}
    ...  password=${temp_password}
    ...  otp_secret=${TST_BALIE_OTP_SECRET}
    ...  new_password=${TST_BALIE_PASSWORD}
  END


*** Keywords ***
Check If Admin User Exists
  VAR  ${view_user_command} =  docker exec ${ADMIN_CONTAINER_NAME} bin/console woopie:user:view "${TST_BALIE_USER}"
  Run Process  ${view_user_command}  shell=True  alias=view_user
  ${stdout} =  Get Process Result  view_user  stdout=True
  ${already_exists} =  Evaluate  'User email@example.org not found.' not in '''${stdout}'''
  RETURN  ${already_exists}
