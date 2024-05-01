*** Settings ***
Documentation       Resource file containing custom keywords for Toegangsbeheer in the Balie.
Library             String
Library             Browser
Library             DebugLibrary
Library             ${CURDIR}/../libraries/QR.py


*** Variables ***
${OTP_CODE_NEW_USER}    ${EMPTY}


*** Keywords ***
Parse QR And Store OTP Code
  [Documentation]  Parse the QR that is generated after creating a new user
  ${img_path} =  Take Screenshot  qr  id=QR-code  fileType=jpeg
  Sleep  1
  ${qr_str} =  Read QR Img  ${img_path}
  Should Not Be Empty  ${qr_str}  msg=No QR code read
  ${otp_code_new_user} =  Get Regexp Matches  ${qr_str}  [A-Z0-9]{52}
  Set Suite Variable  ${OTP_CODE_NEW_USER}  ${otp_code_new_user}[0]
