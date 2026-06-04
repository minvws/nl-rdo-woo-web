*** Settings ***
Documentation       This testcase is used to initialize a new environment.
Resource            ../../resources/Setup.resource


*** Test Cases ***
Do First Login
  [Documentation]  Logs into the Robot Admin account and changes the temporary password if necessary.
  [Tags]  first-login
  Open Browser And BaseUrl
  ${tst_balie_otp_secret} =  Get OTP Secret
  ${temp_password} =  Get File  secrets/.temp_password
  Login Admin
  ...  username=${TST_BALIE_USER}
  ...  password=${temp_password}
  ...  otp_secret=${tst_balie_otp_secret}
  ...  new_password=${TST_BALIE_PASSWORD}
  Remove File  secrets/.temp_password
