*** Settings ***
Documentation       This file contains testcases that can be used to initiate a new environment and getting an OTP.
Resource            ../resources/Setup.resource


*** Test Cases ***
Get Local OTP Code
  [Documentation]  Generate OTP code using local secret
  [Tags]  otp
  ${otp} =  Get Otp  %{SECRET_WOO_LOCAL}
  Log To Console  OTP code: ${otp}

Cleansheet
  [Tags]  cleansheet
  Cleansheet
