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

Organisation Selector Visible
  ${count} =  Get Element Count  //*[@data-e2e-name="organisation-switcher"]
  RETURN  ${count} > 0

Click Manage Organisations
  Click  //*[@data-e2e-name="manage-organisations"]

Add A New Organisation Prefix First Run
  [Arguments]  ${prefix}
  Fill Text  (//input[@data-e2e-name="add-prefix-input"])[last()]  ${prefix}

Add A New Organisation Prefix
  [Arguments]  ${prefix}
  Click  //button[@data-e2e-name="add-prefix-button"]
  Fill Text  (//input[@data-e2e-name="add-prefix-input"])[last()]  ${prefix}

Click Create Organisation
  Click  //*[@data-e2e-name="create-organisation"]

Click Save Organisation
  Click  //*[@id="organisation_form_submit"]
  ${csfr_error_visible} =  Get Element Count  //*[contains(.,'CSRF')]
  IF  ${csfr_error_visible} > 0
    # Retry on CSRF error, temp workaround for https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Log  message=CSRF error found, retrying the click.  level=WARN
    Select Responsible Department  E2E Test Department 1
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

Select Organisation
  [Arguments]  ${organisation}=E2E Test Organisation
  ${count} =  Get Element Count  //*[@data-e2e-name="organisation-switcher"]
  IF  ${count} > 0
    ${already_selected} =  Get Element Count  //*[@data-e2e-name="active-organisation"][contains(.,'${organisation}')]
    IF  not ${already_selected}
      Click Organisation Selector
      Click  //*[@data-e2e-name="organisation-switcher"]//li[contains(.,'${organisation}')]
    END
  END

Create New Organisation
  [Arguments]  ${organisation_name}=EMPTY  ${responsible_department_name}=EMPTY  ${prefix}=EMPTY
  Click Organisation Selector
  Click Manage Organisations
  ${already_exists} =  Get Element Count  //*[@data-e2e-name="organisations-table"][contains(.,'${organisation_name}')]
  IF  not ${already_exists}
    Click  //a[@data-e2e-name="create-organisation"]
    IF  '${organisation_name}' == 'EMPTY' and '${prefix}' == 'EMPTY'
      ${random} =  Generate Random String  5  [NUMBERS][UPPER]
      VAR  ${organisation_name} =  A Random Organisation ${random}
      VAR  ${prefix} =  ${random}
    END
    Fill Text  id=organisation_form_name  ${organisation_name}
    Fill Text  //input[@data-e2e-name="add-prefix-input"]  ${prefix}
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
    ${updated_organisation_name} =  Get Text  id=organisation_form_name
    ${updated_organisation_name} =  Catenate  ${updated_organisation_name}${random}
  END
  Fill Text  id=organisation_form_name  ${updated_organisation_name}
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
  ${nr_of_subjects} =  Get Element Count  //select[@name="organisation_form[departments][0]"]/option
  ${random_number} =  Evaluate  random.randint(1, ${nr_of_subjects})  random
  ${random_index} =  Evaluate  ${random_number} - 1
  Select Options By  //select[@name="organisation_form[departments][0]"]  index  ${random_index}

Open Organisation Details
  [Arguments]  ${organisation}=E2E Test Organisation
  Click  //table[@data-e2e-name="organisations-table"]//td[contains(.,'${organisation}')]/..//a

Click Save Prefixes
  Click  //*[@id="organisation_form_submit"]
  ${csfr_error_visible} =  Get Element Count  //*[contains(.,'CSRF')]
  IF  ${csfr_error_visible} > 0
    # Retry on CSRF error, another instance of https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Log  message=CSRF error found, retrying the click.  level=WARN
    Select Responsible Department  E2E Test Department 1
    Click  //*[@id="organisation_form_submit"]
    Get Element States
    ...  //*[contains(.,'CSRF')]
    ...  not contains
    ...  attached
    ...  message=Error shown, even after retry
  END
  # The following check has been added to check if the save went succesful in parallel execution. If the check fails, the upper keyword will retry it.
  ${prefix_save_error} =  Get Element Count  //*[contains(.,'The prefix can only be set on creation, never updated')]
  IF  ${prefix_save_error} > 0
    Log  message=Saving prefixes failed due to parallel execution  level=WARN
  END
  Should Not Be Equal As Integers  ${prefix_save_error}  1

Add A Random Organisation Prefix
  [Documentation]  Adds a random organisation prefix and returns it. Assumes there are already more then 1 prefixes.
  ${prefix} =  Wait Until Keyword Succeeds  3x  1s  Inner Keyword Add A Random Organisation Prefix
  RETURN  ${prefix}

Inner Keyword Add A Random Organisation Prefix
  Click Organisation Selector
  Click Manage Organisations
  Open Organisation Details  E2E Test Organisation
  Select Responsible Department  E2E Test Department 1
  ${random} =  FakerLibrary.Ean 13
  ${prefix} =  Catenate  E2E-RAND-${random}
  Add A New Organisation Prefix  ${prefix}
  Click Save Prefixes
  RETURN  ${prefix}
