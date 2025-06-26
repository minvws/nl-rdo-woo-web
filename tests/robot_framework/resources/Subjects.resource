*** Settings ***
Documentation       Resource file containing custom keywords for the Subjects section in the Admin portal.
Library             String
Library             Browser
Library             DebugLibrary
Resource            ../resources/Generic.resource

*** Keywords ***
Click Subjects
  Click  "Onderwerpen"

Create New Subject
  [Documentation]  If no params are provided, a random name is generated.
  [Arguments]  ${name}=EMPTY
  IF  '${name}' == 'EMPTY'
    ${random} =  Generate Random String  3  [NUMBERS][UPPER]
    VAR  ${name} =  A Random Subject ${random}
  END
  Click  //a[@data-e2e-name="new-subject"]
  Fill Text  id=subject_name  ${name}
  Click  id=subject_submit
  Success Alert Is Visible  Het onderwerp is aangemaakt
  RETURN  ${name}

Update Subject
  [Documentation]  If no params are provided, random numbers are added to the fields and checkbox is swapped.
  [Arguments]  ${name}  ${updated_name}=EMPTY
  Click  //tbody[@data-e2e-name="subject-list"]//a[contains(.,'${name}')]
  IF  '${updated_name}' == 'EMPTY'
    ${random} =  Generate Random String  1  [NUMBERS]
    ${updated_name} =  Get Text  id=subject_name
    ${updated_name} =  Catenate  ${updated_name}${random}
  END
  Fill Text  id=subject_name  ${updated_name}
  Click  id=subject_submit
  Success Alert Is Visible  Het onderwerp is aangepast

Ensure There Are More Than 10 Subjects
  Click  " Onderwerpen "
  ${nr_of_subjects} =  Get Element Count  //tbody[@data-e2e-name="subject-list"]//tr
  ${missing_subjects} =  Evaluate  max(10 - ${nr_of_subjects} , 0)
  FOR  ${_}  IN RANGE  1  ${missing_subjects}
    ${random_id} =  Generate Random String  5  [NUMBERS][UPPER]
    Create New Subject  Onderwerp ${random_id}
  END
