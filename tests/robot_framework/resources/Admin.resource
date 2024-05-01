*** Settings ***
Documentation       Resource file containing custom keywords for the Balue.
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Library             ${CURDIR}/../libraries/QR.py


*** Variables ***
${BASE_URL_BALIE}       ${EMPTY}
${OTP_CODE}             ${EMPTY}
${TST_BALIE_USER}       ${EMPTY}
${TST_BALIE_PASSWORD}   ${EMPTY}


*** Keywords ***
Login Balie
  [Arguments]  ${username}=${TST_BALIE_USER}  ${password}=${TST_BALIE_PASSWORD}
  Go To  ${BASE_URL_BALIE}
  Fill Text  id=inputEmail  ${username}
  Fill Text  id=inputPassword  ${password}
  Click  " Inloggen "
  Log  ${OTP_CODE}
  ${otp} =  Get Otp  ${OTP_CODE}
  Fill Text  id=auth-code  ${otp}
  Click  " Controleren "
  Get Text  //body  *=  Uitloggen

Logout Balie
  Click  xpath=//*[@data-e2e-name="logout-link"]
