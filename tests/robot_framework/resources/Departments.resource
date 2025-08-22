*** Settings ***
Documentation       Resource file containing custom keywords for the Departments section in the Admin portal.
Library             String
Library             Browser
Library             DebugLibrary
Resource            ../resources/Generic.resource


*** Keywords ***
Click Departments
  Click  "Bestuursorganen"

Change Department Settings
  [Arguments]  ${department}  ${visible}
  Click
  ...  //*[@data-e2e-name="departments-table"]//td[normalize-space()='${department}']/preceding-sibling::td//*[@data-e2e-name="department-details-link"]
  IF  ${visible}
    Check Checkbox  //*[@id="department_public"]
  ELSE
    Uncheck Checkbox  //*[@id="department_public"]
  END
  Click  //*[@id="department_submit"]
  Success Alert Is Visible  Het bestuursorgaan is aangepast

Create New Department
  [Documentation]  If no params are provided, a random name is generated.
  [Arguments]  ${name}=EMPTY  ${short_tag}=EMPTY  ${slug}=EMPTY  ${visible_public}=${TRUE}
  IF  '${name}' == 'EMPTY' and '${short_tag}' == 'EMPTY' and '${slug}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${name} =  A Random Department Called ${random}
    VAR  ${short_tag} =  ${random}
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
  Success Alert Is Visible  Het bestuursorgaan is aangemaakt
  RETURN  ${short_tag}

Update Department
  [Documentation]  If no params are provided, random numbers are added to the fields and checkbox is swapped.
  [Arguments]
  ...  ${short_tag}
  ...  ${updated_name}=EMPTY
  ...  ${updated_short_tag}=EMPTY
  ...  ${updated_slug}=EMPTY
  ...  ${visible_public}=EMPTY
  Click
  ...  //*[@data-e2e-name="departments-table"]//tr[contains(.,'${short_tag}')]//a[@data-e2e-name="department-details-link"]
  IF  '${updated_name}' == 'EMPTY' and '${updated_short_tag}' == 'EMPTY' and '${updated_slug}' == 'EMPTY' and '${visible_public}' == 'EMPTY'
    ${random} =  Generate Random String  1  [NUMBERS]
    ${updated_name} =  Get Text  id=department_name
    ${updated_short_tag} =  Get Text  id=department_shortTag
    ${updated_slug} =  Get Text  id=department_slug
    ${visible_public} =  Get Checkbox State  id=department_public
    ${updated_name} =  Catenate  ${updated_name}${random}
    ${updated_short_tag} =  Catenate  ${updated_short_tag}${random}
    ${updated_slug} =  Catenate  ${updated_slug}${random}
    ${visible_public} =  Evaluate  not ${visible_public}
  END
  Fill Text  id=department_name  ${updated_name}
  Fill Text  id=department_shortTag  ${updated_short_tag}
  Fill Text  id=department_slug  ${updated_slug}
  IF  ${visible_public}
    Check Checkbox  id=department_public
  ELSE
    Uncheck Checkbox  id=department_public
  END
  Click  id=department_submit
  Success Alert Is Visible  Het bestuursorgaan is aangepast

Click Edit Department Landingpage
  [Arguments]  ${abbreviation}
  Click
  ...  //*[@data-e2e-name="departments-table"]//tr[contains(.,'${abbreviation}')]//a[@data-e2e-name="update-landing-page"]

Click Title And Description Tab
  Click  //*[@data-e2e-name="tabs-landingspagina-button-1"]

Click Logo Tab
  Click  //*[@data-e2e-name="tabs-landingspagina-button-2"]

Click Submit Landingpage
  Click  //*[@id="landing_page_submit"]

Upload Logo
  [Arguments]  ${file_path}
  # Removes the logo if it exists, which could happen when rerunning the same test
  ${logo_present} =  Get Element Count  //*[@data-e2e-name="remove-logo"]
  IF  ${logo_present}
    Remove Logo
    Reload
  END
  Upload File By Selector  id=file  ${file_path}
  Success Alert Is Visible
  ...  Logo opgeslagen
  ...  //*[@data-e2e-name="tabs-landingspagina-content-2"]//div[contains(@class, 'bhr-alert--success')]

Remove Logo
  Click  //*[@data-e2e-name="remove-logo"]
  Success Alert Is Visible
  ...  Logo verwijderd
  ...  //*[@data-e2e-name="tabs-landingspagina-content-2"]//div[contains(@class, 'bhr-alert--success')]

Click Department Public URL
  [Arguments]  ${abbreviation}
  Click
  ...  //*[@data-e2e-name="departments-table"]//tr[contains(.,'${abbreviation}')]//a[@data-e2e-name="landing-page-url"]
