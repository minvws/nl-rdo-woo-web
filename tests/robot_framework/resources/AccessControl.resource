*** Comments ***
# robocop: off=no-suite-variable


*** Settings ***
Documentation       Resource file containing custom keywords for Toegangsbeheer in the Balie.
Library             String
Library             Browser
Library             DebugLibrary
Library             ${CURDIR}/../libraries/QR.py
Resource            Admin.resource
Resource            Organisations.resource


*** Keywords ***
Click Access Control
  Click  " Toegangsbeheer "

Create New User
  [Documentation]  Creates the user, logs in once to change the temp password and saves the credentials
  ...  in suite vars.
  [Arguments]  ${role}  ${change_temp_password}=${TRUE}  ${store_creds}=${TRUE}  ${org_prefix}=
  ${unique_id} =  Generate Random String  3  [NUMBERS][UPPER]
  ${unique_id} =  Convert To Lower Case  ${unique_id}
  VAR  ${name} =  ${role}_${org_prefix}_${unique_id}
  VAR  ${email} =  ${role}_${unique_id}@test.email
  Click  "Nieuwe gebruiker aanmaken"
  Fill Text  id=user_create_form_name  ${name}
  Check Checkbox  //input[@value="${role}"]
  Fill Text  id=user_create_form_email  ${email}
  Click  "Account aanmaken"
  Wait For Elements State  //*[@id="user_create_form_email-error"]  detached  timeout=1s
  Get Text  //*[@data-e2e-name="user-info"]  contains  Het account is aangemaakt
  Click  "Download instructies"
  ${password} =  Get Text  //*[@data-e2e-name="user-password"]
  ${otp_secret} =  Parse QR And Return OTP Code
  # Now we login once to change the temp password
  IF  ${change_temp_password}
    Click  xpath=//*[@data-e2e-name="logout-link"]
    ${password} =  Login Admin  ${email}  ${password}  ${otp_secret}
    Click  xpath=//*[@data-e2e-name="logout-link"]
  END
  IF  ${store_creds}
    VAR  ${${role}_email} =  ${email}  scope=suite
    VAR  ${${role}_password} =  ${password}  scope=suite
    VAR  ${${role}_OTP} =  ${otp_secret}  scope=suite
  END
  RETURN  ${name}

Parse QR And Return OTP Code
  [Documentation]  Parse the QR that is generated after creating a new user and returns it
  ${img_path} =  Take Screenshot  qr  id=QR-code  fileType=jpeg
  ${qr_str} =  Read QR Img  ${img_path}
  ${otp_code_new_user} =  Get Regexp Matches  ${qr_str}  [A-Z0-9]{52}
  RETURN  ${otp_code_new_user}[0]

Set Credentials By Role
  [Arguments]  ${role}
  VAR  ${EMAIL} =  ${${role}_EMAIL}  scope=suite
  VAR  ${PASSWORD} =  ${${role}_PASSWORD}  scope=suite
  VAR  ${OTP} =  ${${role}_OTP}  scope=suite

Edit User
  [Arguments]  ${username}  ${new_username}  ${extra_role}
  Click  //div[@data-e2e-name="tabs-gebruikers-content-1"]//a[contains(.,'${username}')]
  Fill Text  id=user_info_form_name  ${new_username}
  Check Checkbox  //input[@value="${extra_role}"]
  Click  //button[@id="user_info_form_submit"]
  ${error_present} =  Get Element Count  //div[@id="user_info_form-error"]//div[contains(., 'CSRF')]
  IF  ${error_present} > 0
    # Retry on CSRF error, another instance of https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Click  //button[@id="user_info_form_submit"]
    Success Alert Is Visible  De gebruiker is gewijzigd
  END

Deactivate User
  [Arguments]  ${username}
  Click  //div[@data-e2e-name="tabs-gebruikers-content-1"]//a[contains(.,'${username}')]
  Click  //button[@id="disable_user_form_submit"]
  Success Alert Is Visible  Account van ${username} is gedeactiveerd.
  Select Access Control Tab  Deactivated users
  Get Text  //div[@data-e2e-name="tabs-gebruikers-content-2"]//tbody//th[contains(.,'${username}')]

Create Test User
  [Arguments]  ${role}  ${organisation}=E2E Test Organisation
  Login Admin
  Select Organisation  ${organisation}
  ${org_abbrev} =  Abbreviate To First Letters  ${organisation}
  Click Access Control
  Create New User  ${role}  org_prefix=${org_abbrev}

Select Access Control Tab
  [Arguments]  ${tab}
  IF  '${tab}' == 'Active users'
    VAR  ${tab_index} =  1
  ELSE IF  '${tab}' == 'Deactivated users'
    VAR  ${tab_index} =  2
  ELSE IF  '${tab}' == 'Active admins'
    VAR  ${tab_index} =  3
  ELSE IF  '${tab}' == 'Deactivated users'
    VAR  ${tab_index} =  4
  END
  Click  //button[@data-e2e-name="tabs-gebruikers-button-${tab_index}"]
