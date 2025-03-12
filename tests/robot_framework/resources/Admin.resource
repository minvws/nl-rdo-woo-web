*** Settings ***
Documentation       Resource file containing custom keywords for the Admin section, or Balie in Dutch.
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Library             ${CURDIR}/../libraries/QR.py
Resource            ../resources/Generic.resource


*** Variables ***
${ATTACHMENT_FILENAME}  ${EMPTY}
${ATTACHMENT_LOCATION}  ${EMPTY}


*** Keywords ***
Go To Admin
  Go To  ${BASE_URL_BALIE}

Login Admin
  [Documentation]  Logs in to the admin section. Will change password if it's the first login.
  [Arguments]  ${username}=${TST_BALIE_USER}
  ...  ${password}=${TST_BALIE_PASSWORD}
  ...  ${otp_secret}=${TST_BALIE_OTP_SECRET}
  ...  ${new_password}=IkLoopNooitVastVandaag
  Go To Admin
  ${already_logged_in} =  Get Element Count  //*[@id="main-nav"][contains(.,'Uitloggen')]
  IF  ${already_logged_in} > 0
    Click Log Out
    Go To Admin
  END
  Fill Text  id=inputEmail  ${username}
  Fill Text  id=inputPassword  ${password}
  Click  " Inloggen "
  Wait For Elements State  //*[@data-e2e-name="login-error"]  detached  timeout=1s
  ${otp} =  Get Otp  ${otp_secret}
  Fill Text  id=auth-code  ${otp}
  Click  " Controleren "
  ${password_change_required} =  Get Element Count  //h2[contains(.,'Werk je wachtwoord bij')]
  IF  ${password_change_required} > 0
    Fill Text  id=change_password_current_password  ${password}
    Fill Text  id=change_password_plainPassword_first  ${new_password}
    Fill Text  id=change_password_plainPassword_second  ${new_password}
    Click  " Wachtwoord aanpassen "
    Get Text  //*[@data-e2e-name="alerts"]  contains  Wachtwoord succesvol gewijzigd
    VAR  ${password}  ${new_password}
  END
  Get Text  //*[@id="main-nav"]  contains  Uitloggen
  Log  Logged in as: ${username}
  RETURN  ${password}

Click Log Out
  Click  //*[@data-e2e-name="logout-link"]

No-Click Logout
  Go To  ${BASE_URL_BALIE}/logout

Upload Attachment
  [Arguments]  ${type}  ${date}=01012022
  Click  " + Bijlage toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${ATTACHMENT_LOCATION}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${ATTACHMENT_FILENAME}  timeout=10s
  Select Options By  //dialog[@open]//select[@name="type"]  text  ${type}
  Type Text  //dialog[@open]//input[@name="formalDate"]  ${date}
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Bijlage '${ATTACHMENT_FILENAME}' is toegevoegd.

Wait For Queue To Empty
  New Page  ${BASE_URL_BALIE}/stats
  Wait Until Keyword Succeeds  10min  15s  Is Queue Empty
  Close Page

Is Queue Empty
  Reload
  Take Screenshot
  VAR  @{elements}
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'es_updates')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'global')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'high')]/td[3]
  ...  //*[@data-e2e-name="rabbitmq-stats"]//tr[contains(.,'ingestor')]/td[3]
  FOR  ${element}  IN  @{elements}
    ${value} =  Get Text  ${element}
    IF  '${value}' != '0'  Fail
  END
