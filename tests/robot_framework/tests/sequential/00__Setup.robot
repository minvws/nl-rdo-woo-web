*** Settings ***
Documentation       This testcase is used to initialize a new environment.
Resource            ../../resources/Setup.resource


*** Test Cases ***
Do First Login
  [Documentation]  Logs into the Robot Admin account and changes the temporary password if necessary.
  [Tags]  first-login
  Set Tenant Context  %{TENANT}
  Open Browser And BaseUrl
  ${tst_balie_otp_secret} =  Get OTP Secret
  ${temp_password} =  Get File  secrets/.temp_password
  Login Admin
  ...  username=${ADMIN_EMAIL}
  ...  password=${temp_password}
  ...  otp_secret=${tst_balie_otp_secret}
  ...  new_password=${ADMIN_PASSWORD}
  Remove File  secrets/.temp_password
