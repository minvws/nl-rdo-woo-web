*** Settings ***
Documentation       Resource file containing custom keywords for the Organisations section in the Admin portal.
Library             String
Library             Browser
Library             DebugLibrary
Library             FakerLibrary


*** Keywords ***
Click Organisation Selector
  Click  //*[@data-e2e-name="organisation-switcher"]

Organisation Selector Should Not Be Available
  Get Element Count  //*[@data-e2e-name="organisation-switcher"]  should not be  1

Click Manage Organisations
  Click  //*[@data-e2e-name="manage-organisations"]

Click Save Organisation
  Click  //*[@data-e2e-name="organisation-form-submit"]
  ${csfr_error_visible} =  Get Element Count  //*[contains(.,'CSRF')]
  IF  ${csfr_error_visible} > 0
    # Retry on CSRF error, temp workaround for https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Log  message=CSRF error found, retrying the click.  level=WARN
    Select Responsible Department  E2E Test Department 1
    Click  //*[@data-e2e-name="organisation-form-submit"]
    Get Element States
    ...  //*[@data-e2e-name="input-errors-organisation_form"]
    ...  not contains
    ...  attached
    ...  message=Error shown, even after retry
  END

Select Responsible Department
  [Arguments]  ${department}
  Select Options By  //select[@name="organisation_form[departments][0]"]  label  ${department}

Select Organisation
  [Arguments]  ${organisation}=E2E Test Organisation
  ${count} =  Get Element Count  //*[@data-e2e-name="organisation-switcher"]
  IF  ${count} > 0
    ${already_selected} =  Get Element Count
    ...  //*[@data-e2e-name="active-organisation"][contains(.,'${organisation}')]
    IF  not ${already_selected}
      Click Organisation Selector
      Click  //*[@data-e2e-name="organisation-switcher"]//li[contains(.,'${organisation}')]
    END
  END

Create New Organisation
  [Arguments]  ${organisation_name}=EMPTY  ${responsible_department_name}=EMPTY  ${prefix}=EMPTY
  Click Organisation Selector
  Click Manage Organisations
  ${already_exists} =  Get Element Count
  ...  //*[@data-e2e-name="organisations-table"][contains(.,'${organisation_name}')]
  IF  not ${already_exists}
    Click  //a[@data-e2e-name="create-organisation"]
    IF  '${organisation_name}' == 'EMPTY' and '${prefix}' == 'EMPTY'
      ${random} =  Generate Random String  5  [NUMBERS][UPPER]
      VAR  ${organisation_name} =  A Random Organisation ${random}
      VAR  ${prefix} =  ${random}
    END
    Fill Text  //input[@data-e2e-name="organisation-name-input"]  ${organisation_name}
    Fill Text  //input[@data-e2e-name="organisation-prefix-input"]  ${prefix}
    IF  '${responsible_department_name}' == 'EMPTY'
      Select Options By  //select[@name="organisation_form[departments][0]"]  index  1
    ELSE
      Select Options By  //select[@name="organisation_form[departments][0]"]  text  ${responsible_department_name}
    END
    Click Save Organisation
  END
  RETURN  ${prefix}  ${organisation_name}

Update Organisation
  [Documentation]  If no params are provided, random numbers are added to the fields and checkbox is swapped.
  [Arguments]
  ...  ${prefix}
  ...  ${updated_organisation_name}=EMPTY
  ...  ${updated_responsible_department_name}=EMPTY
  Click  //table[@data-e2e-name="organisations-table"]//a[contains(.,'${prefix}')]
  IF  '${updated_organisation_name}' == 'EMPTY'
    ${random} =  Generate Random String  1  [NUMBERS]
    ${updated_organisation_name} =  Get Text  //input[@data-e2e-name="organisation-name-input"]
    ${updated_organisation_name} =  Catenate  ${updated_organisation_name}${random}
  END
  Fill Text  //input[@data-e2e-name="organisation-name-input"]  ${updated_organisation_name}
  IF  '${updated_responsible_department_name}' == 'EMPTY'
    Select Random Department
  ELSE
    Select Options By
    ...  //select[@name="organisation_form[departments][0]"]
    ...  text
    ...  ${updated_responsible_department_name}
  END
  Click Save Organisation

Select Random Department
  Wait For Elements State  //select[@name="organisation_form[departments][0]"]  attached
  ${nr_of_subjects} =  Get Element Count  //select[@name="organisation_form[departments][0]"]/option
  ${random_number} =  Evaluate  random.randint(1, ${nr_of_subjects})  random
  ${random_index} =  Evaluate  ${random_number} - 1
  Select Options By  //select[@name="organisation_form[departments][0]"]  index  ${random_index}
