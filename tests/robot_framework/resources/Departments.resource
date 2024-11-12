*** Settings ***
Documentation       Resource file containing custom keywords for the Departments section in the Admin portal.
Library             Browser
Library             DebugLibrary


*** Keywords ***
Click Departments
  Click  "Bestuursorganen"

Change Department Settings
  [Arguments]  ${department}  ${visible}
  Click
  ...  //table[@data-e2e-name="departments-table"]//tr[td[contains(.,'${department}')]]//td//a[@data-e2e-name="department-details-link"]
  IF  ${visible}
    Check Checkbox  //*[@id="department_public"]
  ELSE
    Uncheck Checkbox  //*[@id="department_public"]
  END
  Click  //*[@id="department_submit"]
  Get Text  //*[@data-e2e-name="alerts"]  contains  Het bestuursorgaan is aangepast
