*** Settings ***
Documentation       Resource file containing custom keywords for the Admin section, or Balie in Dutch.
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
${ATTACHMENT_FILENAME}  ${EMPTY}
${ATTACHMENT_LOCATION}  ${EMPTY}


*** Keywords ***
Go To Admin
  Go To  ${BASE_URL_BALIE}

Login Admin
  [Arguments]  ${username}=${TST_BALIE_USER}  ${password}=${TST_BALIE_PASSWORD}
  Go To Admin
  Fill Text  id=inputEmail  ${username}
  Fill Text  id=inputPassword  ${password}
  Click  " Inloggen "
  Log  ${OTP_CODE}
  ${otp} =  Get Otp  ${OTP_CODE}
  Fill Text  id=auth-code  ${otp}
  Click  " Controleren "
  Get Text  //body  *=  Uitloggen

Logout Admin
  Click  xpath=//*[@data-e2e-name="logout-link"]

Click Organisation Selector
  Click  //*[@data-e2e-name="organisation-switcher"]

Click Manage Organisations
  Click  //*[@data-e2e-name="manage-organisations"]

Add A New Organisation Prefix
  [Arguments]  ${prefix}
  Click  //*[@id="js-add-widget"]
  ${element_count} =  Get Element Count
  ...  //div[@id="js-widgets-container"]//input[starts-with(@id,'organisation_form_documentPrefixes')]
  Fill Text  //input[starts-with(@id,'organisation_form_documentPrefixes_${element_count-1}')]  ${prefix}

Click Save Prefixes
  Click  //*[@id="organisation_form_submit"]
  ${error_present} =  Get Element Count  //div[@id="organisation_form-error"]
  IF  ${error_present} > 0
    # Retry, temp workaround for https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Select Responsible Department  ministerie van Volksgezondheid, Welzijn en Sport
    Click  //*[@id="organisation_form_submit"]
    Get Element States
    ...  //div[@id="organisation_form-error"]
    ...  not contains
    ...  attached
    ...  message=Error shown, even after retry
  END

Select Responsible Department
  [Arguments]  ${department}
  Select Options By  //select[@name="organisation_form[departments][0]"]  label  ${department}

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
  New Page  http://localhost:8000/balie/stats
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

Select Organisation
  [Arguments]  ${organisation}=Programmadirectie Openbaarheid
  Click  //*[@data-e2e-name="organisation-switcher"]
  Click  //*[@data-e2e-name="organisation-switcher"]//li[contains(.,'${organisation}')]

Open Organisation Details
  [Arguments]  ${organisation}=Programmadirectie Openbaarheid
  Click  //table[@data-e2e-name="organisations-table"]//td[contains(.,'${organisation}')]/..//a

Create Random Subjects
  Click  " Onderwerpen "
  FOR  ${i}  IN RANGE  1  10
    ${random_id} =  Generate Random String  3  [NUMBERS][UPPER]
    Create New Subject  Onderwerp ${random_id}
  END

Create New Subject
  [Arguments]  ${subject_name}
  Click  //a[@data-e2e-name="new-subject"]
  Fill Text  //input[@id="subject_name"]  ${subject_name}
  Click  id=subject_submit
  Get Text  //div[@data-e2e-name="alerts"]  contains  Het onderwerp is aangemaakt
