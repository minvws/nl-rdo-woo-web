*** Settings ***
Documentation       Resource file containing custom keywords for the Organisations section in the Admin portal.
Library             String
Library             Browser
Library             DebugLibrary


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

Add A New Organisation Prefix
  [Arguments]  ${prefix}
  Click  //*[@id="js-add-widget"]
  ${element_count} =  Get Element Count
  ...  //div[@id="js-widgets-container"]//input[starts-with(@id,'organisation_form_documentPrefixes')]
  Fill Text  //input[starts-with(@id,'organisation_form_documentPrefixes_${element_count-1}')]  ${prefix}

Click Create Organisation
  Click  //*[@data-e2e-name="create-organisation"]

Click Save Organisation
  Click  //*[@id="organisation_form_submit"]
  ${error_present} =  Get Element Count  //div[@id="organisation_form-error"]
  IF  ${error_present} > 0
    # Retry on CSRF error, temp workaround for https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
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

Select Organisation
  [Arguments]  ${organisation}=Programmadirectie Openbaarheid
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
      VAR  ${organisation_name}  A Random Organisation ${random}
      VAR  ${prefix}  ${random}
    END
    Fill Text  id=organisation_form_name  ${organisation_name}
    Fill Text  id=organisation_form_documentPrefixes_0_prefix  ${prefix}
    IF  '${responsible_department_name}' == 'EMPTY'
      Select Options By  //select[@name="organisation_form[departments][0]"]  index  1
    ELSE
      Select Options By  //select[@name="organisation_form[departments][0]"]  text  ${responsible_department_name}
    END
    Click Save Organisation
  END
  RETURN  ${prefix}

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
  [Arguments]  ${organisation}=Programmadirectie Openbaarheid
  Click  //table[@data-e2e-name="organisations-table"]//td[contains(.,'${organisation}')]/..//a

Click Save Prefixes
  Click  //*[@id="organisation_form_submit"]
  ${error_present} =  Get Element Count  //div[@id="organisation_form-error"]
  IF  ${error_present} > 0
    # Retry, temp workaround for CSRF issue https://github.com/minvws/nl-rdo-woo-web-private/issues/3500
    Select Responsible Department  ministerie van Volksgezondheid, Welzijn en Sport
    Click  //*[@id="organisation_form_submit"]
    Get Element States
    ...  //div[@id="organisation_form-error"]
    ...  not contains
    ...  attached
    ...  message=Error shown, even after retry
  END
