*** Settings ***
Documentation       Resource file containing custom keywords for the Departments section in the Admin portal.
Library             String
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

Create New Department
  [Documentation]  If no params are provided, a random name is generated.
  [Arguments]  ${name}=EMPTY  ${short_tag}=EMPTY  ${slug}=EMPTY  ${visible_public}=${TRUE}
  IF  '${name}' == 'EMPTY' and '${short_tag}' == 'EMPTY' and '${slug}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${name}  A Random Department Called ${random}
    VAR  ${short_tag}  ${random}
    ${slug} =  Convert To Lower Case  ${random}
  END
  Click  //a[@data-e2e-name="new-department"]
  Fill Text  id=department_name  ${name}
  Fill Text  id=department_shortTag  ${short_tag}
  Fill Text  id=department_slug  ${slug}
  IF  ${visible_public}
    Check Checkbox  id=department_public
  ELSE
    Uncheck Checkbox  id=department_public
  END
  Click  id=department_submit
  Get Text  //*[@data-e2e-name="alerts"]  contains  Het bestuursorgaan is aangemaakt
  RETURN  ${short_tag}

Update Department
  [Documentation]  If no params are provided, random numbers are added to the fields and checkbox is swapped.
  [Arguments]
  ...  ${short_tag}
  ...  ${updated_name}=EMPTY
  ...  ${updated_short_tag}=EMPTY
  ...  ${updated_slug}=EMPTY
  ...  ${visible_public}=EMPTY
  Click  //a[@data-e2e-name="department-details-link"][contains(.,'${short_tag}')]
  IF  '${updated_name}' == 'EMPTY' and '${updated_short_tag}' == 'EMPTY' and '${updated_slug}' == 'EMPTY' and '${visible_public}' == 'EMPTY'
    ${random} =  Generate Random String  1  [NUMBERS]
    ${updated_name} =  Get Text  id=department_name
    ${updated_short_tag} =  Get Text  id=department_shortTag
    ${updated_slug} =  Get Text  id=department_slug
    ${visible_public} =  Get Checkbox State  id=department_public
    ${updated_name} =  Catenate  ${updated_name}${random}
    ${updated_short_tag} =  Catenate  ${updated_short_tag}${random}
    ${updated_slug} =  Catenate  ${updated_slug}${random}
  END
  Fill Text  id=department_name  ${updated_name}
  Fill Text  id=department_shortTag  ${updated_short_tag}
  Fill Text  id=department_slug  ${updated_slug}
  IF  ${visible_public}
    Uncheck Checkbox  id=department_public
  ELSE
    Check Checkbox  id=department_public
  END
  Click  id=department_submit
  Get Text  //*[@data-e2e-name="alerts"]  contains  Het bestuursorgaan is aangepast
