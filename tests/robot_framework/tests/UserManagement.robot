*** Settings ***
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../resources/UserManagement.resource
Resource            ../resources/Admin.resource
Resource            ../resources/Setup.resource
Suite Setup         CI Suite Setup
Test Teardown       Logout Admin
Test Tags           usermanagement


*** Variables ***
${CURRENT_EPOCH}            ${EMPTY}
${OTP_CODE}                 ${EMPTY}
${BASE_URL}                 localhost:8000
${BASE_URL_BALIE}           localhost:8000/balie/dossiers
${TST_BALIE_USER}           email@example.org
${TST_BALIE_PASSWORD}       IkLoopNooitVastVandaag
${OTP_CODE_NEW_USER}        ${EMPTY}
${NEW_USER_EMAIL}           ${CURRENT_EPOCH}@example.org
${NEW_USER_TEMP_PASSWORD}   ${EMPTY}


*** Test Cases ***
# AA-001 - Login As A Super Admin
#  Login Admin
#  Click  "Toegangsbeheer"
# AA-002 - Show List All Users
#  Login Admin
#  Click  "Toegangsbeheer"
#  Get Text  //*[@data-e2e-name="user-table"]  contains  Super beheerder
# AA-003 - Create Another Super Admin
#  Login Admin
#  Click  "Toegangsbeheer"
#  Create New User  super_admin  SuperAdmin${CURRENT_EPOCH}  superadmin${CURRENT_EPOCH}@test.org  superadmin
Create New User
  [Documentation]  Login to Admin and create a new user
  Login Admin
  Click  "Toegangsbeheer"
  Create New User  super_admin  Testgebruiker${CURRENT_EPOCH}  ${CURRENT_EPOCH}@example.org

Login New User
  Go To Admin
  Login User  ${NEW_USER_EMAIL}  ${NEW_USER_TEMP_PASSWORD}  ${OTP_CODE_NEW_USER}

Edit User
  [Documentation]  Login to Admin and edit User
  Login Admin
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
  [Documentation]  Login to Admin and deactivate user
  Login Admin
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
  [Documentation]  Login to Admin and activate user
  Login Admin
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  *=  Deze gebruiker is momenteel gedeactiveerd.
  Click  "Account activeren"
  Get Text  //body  *=  Account van Testgebruiker2_${CURRENT_EPOCH} is geactiveerd.
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Get Text  //body  not contains  Account van Testgebruiker2_${CURRENT_EPOCH} is gedeactiveerd.

Password Reset
  [Documentation]  Login to Admin and reset password
  Login Admin
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
  [Documentation]  Login to Admin and reset 2FA
  Login Admin
  Click  "Toegangsbeheer"
  Reload
  Click  "Testgebruiker2_${CURRENT_EPOCH}"
  Click  xpath=//*[@data-e2e-name="2fa-reset-button"]
  Click  "Ja, reset de twee factor code"
  Get Text  //body  *=  Dit account is bijgewerkt
  Click  "Download instructies"
