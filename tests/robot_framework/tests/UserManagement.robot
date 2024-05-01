*** Settings ***
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../resources/UserManagement.resource
Resource            ../resources/Admin.resource
Resource            ../resources/Setup.resource
Suite Setup         CI Suite Setup
Test Teardown       Logout Balie
Test Tags           usermanagement


*** Variables ***
${CURRENT_EPOCH}        ${EMPTY}
${OTP_CODE}             ${EMPTY}
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie/dossiers
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag


*** Test Cases ***
Create New User
  [Documentation]  Login to Balie and create a new user
  Login Balie
  Click  "Toegangsbeheer"
  Get Text  //body  *=  Super beheerder
  Click  "Nieuwe gebruiker aanmaken"
  Fill Text  id=user_create_form_name  Testgebruiker${CURRENT_EPOCH}
  Check Checkbox  id=user_create_form_roles_4
  Fill Text  id=user_create_form_email  ${CURRENT_EPOCH}@example.org
  ${new_user_email} =  Set Variable  ${CURRENT_EPOCH}@example.org
  Set Suite Variable  ${NEW_USER_EMAIL}
  Click  "Account aanmaken"
  Get Text  //body  *=  Het account is aangemaakt
  Click  "Download instructies"
  Get Text  //body  *=  Login instructies voor Testgebruiker${CURRENT_EPOCH}
  Get Text  //body  *=  ${CURRENT_EPOCH}@example.org
  ${element} =  Get Element  xpath=//*[@data-e2e-name="user-password"]
  ${new_user_temp_password} =  Get Text  ${element}
  Set Suite Variable  ${NEW_USER_TEMP_PASSWORD}
  Parse QR And Store OTP Code

Login New User
  Go To  ${BASE_URL_BALIE}
  Fill Text  id=inputEmail  ${NEW_USER_EMAIL}
  Fill Text  id=inputPassword  ${NEW_USER_TEMP_PASSWORD}
  Click  " Inloggen "
  ${otp} =  Get Otp  ${OTP_CODE_NEW_USER}
  Fill Text  id=auth-code  ${otp}
  Click  " Controleren "
  Get Text  //body  *=  Testgebruiker${CURRENT_EPOCH}
  Get Text  //body  *=  ${NEW_USER_EMAIL}
  Fill Text  id=change_password_current_password  ${NEW_USER_TEMP_PASSWORD}
  Fill Text  id=change_password_plainPassword_first  NieuweGebruikerWachtwoord
  Fill Text  id=change_password_plainPassword_second  NieuweGebruikerWachtwoord
  Click  " Wachtwoord aanpassen "
  Get Text  //body  *=  Testgebruiker${CURRENT_EPOCH}
  # Gebruiker heeft alleen lezen rechten en mag geen toegang hebben tot "Toegangsbeheer" en de mogelijkheid om een (besluit)dossier aan te maken
  Get Text  //body  not contains  Toegangsbeheer
  Get Text  //body  not contains  Nieuw besluit (dossier) aanmaken

Edit User
  [Documentation]  Login to Balie and edit User
  Login Balie
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker${CURRENT_EPOCH}"
  Get Text  //body  *=  Testgebruiker${CURRENT_EPOCH}
  Get Text  //body  *=  ${CURRENT_EPOCH}@example.org
  Get Checkbox State  id=user_info_form_roles_4  ==  checked
  Type Text  id=user_info_form_name  Testgebruiker2_${CURRENT_EPOCH}  delay=50 ms  clear=Yes
  Check Checkbox  id=user_info_form_roles_3
  Click  "Opslaan"
  Get Text  //body  *=  De gebruiker is gewijzigd
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  *=  Testgebruiker2_${CURRENT_EPOCH}
  Get Text  //body  *=  ${CURRENT_EPOCH}@example.org
  Get Checkbox State  id=user_info_form_roles_3  ==  checked
  Get Checkbox State  id=user_info_form_roles_4  ==  checked

Deactivate User
  [Documentation]  Login to Balie and deactivate user
  Login Balie
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  *=  Testgebruiker2_${CURRENT_EPOCH}
  Get Text  //body  *=  ${CURRENT_EPOCH}@example.org
  Click  "Account deactiveren"
  Get Text  //body  *=  Account van Testgebruiker2_${CURRENT_EPOCH} is gedeactiveerd.
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  *=  Deze gebruiker is momenteel gedeactiveerd.

Activate User
  [Documentation]  Login to Balie and activate user
  Login Balie
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  *=  Deze gebruiker is momenteel gedeactiveerd.
  Click  "Account activeren"
  Get Text  //body  *=  Account van Testgebruiker2_${CURRENT_EPOCH} is geactiveerd.
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  not contains  Account van Testgebruiker2_${CURRENT_EPOCH} is gedeactiveerd.

Password Reset
  [Documentation]  Login to Balie and reset password
  Login Balie
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Click  xpath=//*[@data-e2e-name="password-reset-button"]
  Get Element States  xpath=//*[@data-e2e-name="password-reset-instructions"]  contains  visible
  Click  "Ja, reset het wachtwoord"
  Get Text  //body  *=  Dit account is bijgewerkt
  Click  "Download instructies"
  Get Text  //body  *=  Login instructies voor Testgebruiker2_${CURRENT_EPOCH}
  Get Text  //body  *=  ${CURRENT_EPOCH}@example.org

2FA Reset
  [Documentation]  Login to Balie and reset 2FA
  Login Balie
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Click  xpath=//*[@data-e2e-name="2fa-reset-button"]
  Click  "Ja, reset de twee factor code"
  Get Text  //body  *=  Dit account is bijgewerkt
  Click  "Download instructies"
