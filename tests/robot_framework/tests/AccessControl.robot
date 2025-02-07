*** Settings ***
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../resources/Setup.resource
Resource            ../resources/AccessControl.resource
Resource            ../resources/Departments.resource
Resource            ../resources/Subjects.resource
Suite Setup         Suite Setup
Test Teardown       Run Keyword If Test Failed  Click Log Out
Test Tags           accesscontrol


*** Variables ***
${EMAIL}        ${EMPTY}
${PASSWORD}     ${EMPTY}
${OTP}          ${EMPTY}


*** Test Cases ***
Access Control
  [Template]  Verify Permissions On Access Control
  # ${role}  ${create}  ${read}  ${update}  ${delete}  ${organisation_only}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}
  organisation_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}
  global_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}

Departments
  [Template]  Verify Permissions On Departments
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${FALSE}  ${FALSE}
  global_admin  ${FALSE}  ${FALSE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}

Subjects
  [Template]  Verify Permissions On Subjects
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${TRUE}  ${TRUE}  ${TRUE}
  global_admin  ${FALSE}  ${FALSE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}

Organisations
  [Template]  Verify Permissions On Organisations
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${FALSE}  ${FALSE}
  global_admin  ${TRUE}  ${TRUE}  ${TRUE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin
  Create New Organisation  Test Org 1  ministerie van Algemene Zaken  TESTORG1
  Create Test User  organisation=Programmadirectie Openbaarheid  role=super_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=global_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=organisation_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=dossier_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=view_access
  Create Test User  organisation=Test Org 1  role=view_access

Verify Permissions On Access Control
  [Arguments]  ${role}  ${create}  ${read}  ${update}  ${delete}  ${organisation_only}
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  not ${read}
    Menu Does Not Contain Item  Toegangsbeheer
  ELSE
    IF  ${create} and ${update} and ${delete}
      Click Access Control
      ${test_username} =  Create New User  view_access  change_temp_password=${False}  store_creds=${FALSE}
      ${new_username} =  Catenate  ${test_username}_edit
      Click Access Control
      Edit User  ${test_username}  ${new_username}  dossier_admin
      Deactivate User  ${new_username}
    END
    IF  ${organisation_only}
      User List Does Not Contain Users From The Other Organisation
    END
  END
  Click Log Out

Menu Does Not Contain Item
  [Arguments]  ${item}
  Get Text  //*[@id="main-nav"]  not contains  ${item}

User List Does Not Contain Users From The Other Organisation
  Click Access Control
  Get Element Count  //div[@data-e2e-name="tab1"]//th[contains(.,'_TO1_')]  should be  0

Verify Permissions On Departments
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  not ${read}
    Menu Does Not Contain Item  Bestuursorganen
  ELSE
    IF  ${create} and ${update}
      Click Departments
      ${short_tag} =  Create New Department
      Update Department  ${short_tag}
    END
  END
  Click Log Out

Verify Permissions On Subjects
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  not ${read}
    Menu Does Not Contain Item  Onderwerpen
  ELSE
    IF  ${create} and ${update}
      Click Subjects
      ${name} =  Create New Subject
      Update Subject  ${name}
    END
  END
  Click Log Out

Verify Permissions On Organisations
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  not ${read}
    Organisation Selector Should Not Be Available
  ELSE
    IF  ${create} and ${update}
      ${prefix} =  Create New Organisation
      Update Organisation  ${prefix}
    END
  END
  Click Log Out
