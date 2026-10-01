*** Settings ***
Documentation       Resource file containing custom keywords for the Admin section, or Balie in Dutch.
Library             String
Library             ${CURDIR}/../libraries/QR.py
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../resources/Generic.resource


*** Variables ***
${ATTACHMENT_FILENAME}  ${EMPTY}
${ATTACHMENT_LOCATION}  ${EMPTY}
${ADMIN_USER}           ${EMPTY}
${ADMIN_PASSWORD}       ${EMPTY}
${ADMIN_OTP_SECRET}     ${EMPTY}
${URL_ADMIN}            ${EMPTY}


*** Keywords ***
Go To Admin
  Go To  ${URL_ADMIN}

Login Admin
  [Documentation]  Logs in to the admin section. Will change password if it's the first login.
  [Arguments]  ${username}=${ADMIN_USER}
  ...  ${password}=${ADMIN_PASSWORD}
  ...  ${otp_secret}=${ADMIN_OTP_SECRET}
  ...  ${new_password}=IkLoopNooitVastVandaag
  Go To Admin
  # If we are already logged in and a specific user is provided, we logout
  ${already_logged_in} =  Get Element Count  //*[@data-e2e-name="logout-link"]
  IF  ${already_logged_in} > 0 and '${username}' == '${ADMIN_USER}'
    Log  Already logged in with default user
  ELSE
    IF  ${already_logged_in} > 0 and '${username}' != '${ADMIN_USER}'
      Click Log Out
      # Now we get sent back to public, which is weird imo
      Go To Admin
    END
    # Inloggen
    Fill Text  id=inputEmail  ${username}
    Fill Text  id=inputPassword  ${password}
    Click  //*[@data-e2e-name="login"]
    Parse Potential Login Error
    ${otp} =  Get Otp  ${otp_secret}
    Fill Text  id=auth-code  ${otp}
    Click  //*[@data-e2e-name="check"]
    Parse Potential Otp Error
    ${password_change_required} =  Get Element Count  //*[@data-e2e-name="update-password-required"]
    IF  ${password_change_required} > 0
      Fill Text  id=change_password_current_password  ${password}
      Fill Text  id=change_password_plainPassword_first  ${new_password}
      Fill Text  id=change_password_plainPassword_second  ${new_password}
      Click  //*[@data-e2e-name="confirm-password-change"]
      Success Alert Is Visible  Wachtwoord succesvol gewijzigd
      VAR  ${password} =  ${new_password}
    END
    Get Text  //*[@id="main-nav"]  contains  Uitloggen
    Log  Logged in as: ${username}
  END
  RETURN  ${password}

Parse Potential Login Error
  ${login_error_visible} =  Get Element Count  //*[@data-e2e-name="login-error"]
  IF  ${login_error_visible}
    ${login_error} =  Get Text  //*[@data-e2e-name="login-error"]
    IF  'Te veel onjuiste inlogpogingen' in '${login_error}'
      Fatal Error
      ...  Login gave 'Too many failed login attempts', did you open a new terminal after running 'task rf:init'?
    ELSE
      Fatal Error  ${login_error}
    END
  END

Parse Potential Otp Error
  ${otp_error_visible} =  Get Element Count  //*[@id="auth-code-error"]
  IF  ${otp_error_visible}
    Fatal Error  Login failed: OTP code was rejected (auth-code-error is visible)
  END

Click Log Out
  Click  //*[@data-e2e-name="logout-link"]

No-Click Logout
  Go To  ${URL_ADMIN}/logout

Upload Attachment
  [Arguments]  ${type}  ${date}=01012022
  Click  //*[@data-e2e-name="attachments"]//*[@data-e2e-name="add-file"]
  Upload File By Selector  //dialog[@open]//*[@data-e2e-name="uploadUuid-input"]  ${ATTACHMENT_LOCATION}
  Wait For Condition  Text  //dialog[@open]//*[@data-e2e-name="uploaded-file"]  contains  ${ATTACHMENT_FILENAME}
  Select Options By  //dialog[@open]//*[@data-e2e-name="type-select"]  text  ${type}
  Type Text  //dialog[@open]//*[@data-e2e-name="formalDate-input"]  ${date}
  Click  //dialog[@open]//*[@data-e2e-name="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Bijlage '${ATTACHMENT_FILENAME}' is toegevoegd.

Wait For Queue To Empty
  New Page  ${URL_ADMIN}/stats
  Wait Until Keyword Succeeds  10min  5s  Is Queue Empty
  Close Page

Is Queue Empty
  Reload
  VAR  @{elements} =
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'es_updates')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'global')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'high')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'ingestor')]/td[3]
  FOR  ${element}  IN  @{elements}
    ${value} =  Get Text  ${element}
    IF  '${value}' == 'not available'
      Fail  RabbitMQ stats not yet available, retrying...
    END
    IF  '${value}' != '0'  Fail
  END
