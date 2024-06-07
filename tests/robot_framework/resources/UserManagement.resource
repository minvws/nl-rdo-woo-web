*** Settings ***
Documentation       Resource file containing custom keywords for Toegangsbeheer in the Balie.
Library             String
Library             Browser
Library             DebugLibrary
Library             ${CURDIR}/../libraries/QR.py


*** Variables ***
${OTP_CODE_NEW_USER}    ${EMPTY}


*** Keywords ***
Create New User
  [Arguments]  ${role}  ${name}  ${email}
  Click  "Nieuwe gebruiker aanmaken"
  Fill Text  id=user_create_form_name  ${name}
  Check Checkbox  xpath=//*[@value="${role}"]
  Fill Text  id=user_create_form_email  ${email}
  ${new_user_email} =  Set Variable  ${email}
  Set Suite Variable  ${NEW_USER_EMAIL}
  Click  "Account aanmaken"
  Get Text  //body  *=  Het account is aangemaakt
  Click  "Download instructies"
  Get Text  //body  *=  Login instructies voor ${name}
  Get Text  //body  *=  ${email}
  ${element} =  Get Element  xpath=//*[@data-e2e-name="user-password"]
  ${password} =  Get Text  ${element}
  ${otp_token} =  Parse QR And Return OTP Code
  Set Suite Variable  ${NEW_USER_TEMP_PASSWORD}  ${password}
  Set Suite Variable  ${OTP_CODE_NEW_USER}  ${otp_token}

Login User
  [Arguments]  ${email}  ${password}  ${otp_token}
  Go To Admin
  Fill Text  id=inputEmail  ${email}
  Fill Text  id=inputPassword  ${password}
  Click  " Inloggen "
  ${otp} =  Get Otp  ${otp_token}
  Fill Text  id=auth-code  ${otp}
  Click  " Controleren "
  Get Text  //body  *=  Testgebruiker${CURRENT_EPOCH}
  Get Text  //body  *=  ${email}
  ${old_timeout} =  Set Browser Timeout  1s
  ${present} =  Run Keyword And Return Status  Get Element  id=password-instructions
  Set Browser Timeout  ${old_timeout}
  IF  ${present}
    Fill Text  id=change_password_current_password  ${NEW_USER_TEMP_PASSWORD}
    Fill Text  id=change_password_plainPassword_first  NieuweGebruikerWachtwoord
    Fill Text  id=change_password_plainPassword_second  NieuweGebruikerWachtwoord
    Click  " Wachtwoord aanpassen "
    Get Text  //body  contains  Wachtwoord succesvol gewijzigd
  END

Parse QR And Return OTP Code
  [Documentation]  Parse the QR that is generated after creating a new user and returns it
  ${img_path} =  Take Screenshot  qr  id=QR-code  fileType=jpeg
  Sleep  1
  ${qr_str} =  Read QR Img  ${img_path}
  Should Not Be Empty  ${qr_str}  msg=No QR code read
  ${otp_code_new_user} =  Get Regexp Matches  ${qr_str}  [A-Z0-9]{52}
  RETURN  ${otp_code_new_user}[0]
