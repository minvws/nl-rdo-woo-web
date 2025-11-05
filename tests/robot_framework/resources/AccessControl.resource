*** Comments ***
# robocop: off=no-suite-variable,non-local-variables-should-be-uppercase


*** Settings ***
Documentation       Resource file containing custom keywords for Toegangsbeheer in the Balie.
Library             String
Library             ${CURDIR}/../libraries/QR.py
Library             Browser
Library             DebugLibrary
Resource            Admin.resource
Resource            Organisations.resource


*** Keywords ***
Click Access Control
  Click  //*[@data-e2e-name="nav-access-control"]

Create New User
  [Documentation]  Creates the user, logs in once to change the temp password and saves the credentials
  ...  in suite vars.
  [Arguments]  ${role}  ${change_temp_password}=${TRUE}  ${store_creds}=${TRUE}
  ${unique_id} =  Generate Random String  3  [NUMBERS][UPPER]
  ${unique_id} =  Convert To Lower Case  ${unique_id}
  VAR  ${name} =  ${role}_${unique_id}
  VAR  ${email} =  ${role}_${unique_id}@test.email
  Click  //*[@data-e2e-name="create-user"]
  Fill Text  id=user_create_form_name  ${name}
  Check Checkbox  //input[@value="${role}"]
  Fill Text  id=user_create_form_email  ${email}
  Click  id=user_create_form_submit
  Wait For Elements State  //*[@id="user_create_form_email-error"]  detached  timeout=1s
  Get Text  //*[@data-e2e-name="user-info"]  contains  Het account is aangemaakt
  Click  //*[@data-e2e-name="download-instructions"]
  ${password} =  Get Text  //*[@data-e2e-name="user-password"]
  ${otp_secret} =  Parse QR And Return OTP Code
  # Now we login once to change the temp password
  IF  ${change_temp_password}
    Click  xpath=//*[@data-e2e-name="logout-link"]
    ${password} =  Login Admin  ${email}  ${password}  ${otp_secret}
    Click  xpath=//*[@data-e2e-name="logout-link"]
  END
  IF  ${store_creds}
    VAR  ${${ROLE}_EMAIL} =  ${email}  scope=suite
    VAR  ${${ROLE}_PASSWORD} =  ${password}  scope=suite
    VAR  ${${ROLE}_OTP} =  ${otp_secret}  scope=suite
  END
  RETURN  ${name}

Parse QR And Return OTP Code
  [Documentation]  Parse the QR that is generated after creating a new user and returns it
  ${img_path} =  Take Screenshot  qr  id=QR-code  fileType=jpeg
  ${qr_str} =  Read QR Img  ${img_path}
  ${otp_code_new_user} =  Get Regexp Matches  ${qr_str}  [A-Z0-9]{52}
  RETURN  ${otp_code_new_user}[0]

Edit User
  [Arguments]  ${username}  ${new_username}  ${extra_role}
  Select User  ${username}
  Fill Text  id=user_info_form_name  ${new_username}
  Check Checkbox  //input[@value="${extra_role}"]
  Click With CSRF Retry  //button[@id="user_info_form_submit"]
  Success Alert Is Visible  De gebruiker is gewijzigd

Deactivate User
  [Arguments]  ${username}  ${tab}
  Select User  ${username}  ${tab}
  Click With CSRF Retry  //button[@id="disable_user_form_submit"]
  Success Alert Is Visible  Account van ${username} is gedeactiveerd.
  Select Access Control Tab  Deactivated users
  Get Text  //div[@data-e2e-name="tabs-gebruikers-content-2"]//tbody//th[contains(.,'${username}')]

Create Test User
  [Arguments]  ${role}  ${organisation}=E2E Test Organisation
  Login Admin
  Select Organisation  ${organisation}
  Click Access Control
  Create New User  ${role}

Select User
  [Arguments]  ${username}  ${tab}=Active users
  Select Access Control Tab  ${tab}
  Click With CSRF Retry  //*[@data-e2e-name="user-table"]//a[contains(.,'${username}')]

Get Access Control Tab ID
  [Arguments]  ${title}
  IF  '${title}' == 'Active users'
    RETURN  1
  ELSE IF  '${title}' == 'Deactivated users'
    RETURN  2
  ELSE IF  '${title}' == 'Active admins'
    RETURN  3
  ELSE IF  '${title}' == 'Deactivated admins'
    RETURN  4
  ELSE
    Fatal Error  Value '${title}' is not an accepted value.
  END

Select Access Control Tab
  [Arguments]  ${tab}
  ${tab_id} =  Get Access Control Tab ID  ${tab}
  Wait Until Keyword Succeeds  3x  1s  Inner Keyword Select Access Control Tab  ${tab_id}

Inner Keyword Select Access Control Tab
  [Arguments]  ${tab_id}
  Click  //*[@data-e2e-name="tabs-gebruikers-button-${tab_id}"]
  Wait For Elements State  //*[@data-e2e-name="tabs-gebruikers-content-${tab_id}"]  visible

Access Control Tab Should Not Be Visible
  [Arguments]  ${tab}
  ${tab_id} =  Get Access Control Tab ID  ${tab}
  Get Element States  //*[@data-e2e-name="tabs-gebruikers-button-${tab_id}"]  contains  detached

Get User Name Of User By Org
  [Documentation]  Returns the first user within that org
  [Arguments]  ${organisation_name}
  ${user_name} =  Get Text  (//*[@data-e2e-name="user-table"]//td[contains(.,'${organisation_name}')]/../th/a)[1]
  ${user_name} =  Strip String  ${user_name}
  RETURN  ${user_name}

Get Current Logged In Username
  ${user_name} =  Get Text  //nav[@id="main-nav"]//a[@href='/balie/profiel']
  RETURN  ${user_name}
