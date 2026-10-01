*** Comments ***
# robocop: off=too-many-arguments


*** Settings ***
Documentation       Tests that focus on the access control within the Balie.
...                 Split into AccessControl.robot and AccessControlDossiers.robot to keep
...                 individual pabot suites balanced. "Dossiers" and "Documents" stay together
...                 in AccessControlDossiers.robot -- the latter needs a dossier the former creates.
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../../resources/AccessControl.resource
Resource            ../../resources/Departments.resource
Resource            ../../resources/Inquiry.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Close Browser
Test Teardown       Run Keyword If Test Failed  No-Click Logout
Test Tags           ci  accesscontrol


*** Test Cases ***
Users
  [Template]  Verify Permissions On Users
  # ${role}  ${create}  ${read}  ${update}  ${delete}  ${organisation_only}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}
  organisation_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}

Super Admin
  [Template]  Verify Permissions On Super Admin
  # ${role}  ${update}
  super_admin  ${TRUE}
  organisation_admin  ${FALSE}

Departments
  [Template]  Verify Permissions On Departments
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${TRUE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}

Department Landingpages
  [Documentation]  The order of execution is important here, the super admin should go first so the org admin sees the edit link.
  [Template]  Verify Permissions On Department Landingpages
  # ${role}  ${update}  ${organisation_only}
  super_admin  ${TRUE}  ${FALSE}
  organisation_admin  ${TRUE}  ${TRUE}
  dossier_admin  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}

Subjects
  [Template]  Verify Permissions On Subjects
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${TRUE}  ${TRUE}  ${TRUE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}

Organisations
  [Template]  Verify Permissions On Organisations
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${FALSE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Create New Organisation  Test Org 1  E2E Test Department 1  TESTORG1
  Create Test User  organisation=Test Org 1  role=super_admin
  Create Test User  organisation=E2E Test Organisation  role=super_admin
  Create Test User  organisation=E2E Test Organisation  role=organisation_admin
  Create Test User  organisation=E2E Test Organisation  role=dossier_admin
  Create Test User  organisation=E2E Test Organisation  role=view_access

Verify Permissions On Users
  [Arguments]  ${role}  ${create}  ${read}  ${update}  ${delete}  ${organisation_only}
  Login Admin With Role  ${role}
  IF  not ${read}
    Menu Does Not Contain Item  Toegangsbeheer
  ELSE
    IF  ${create} and ${update} and ${delete}
      Click Access Control
      ${test_username} =  Create New User  view_access  change_temp_password=${False}  store_creds=${FALSE}
      ${new_username} =  Catenate  ${test_username}_edit
      Click Access Control
      Edit User  ${test_username}  ${new_username}  dossier_admin
      Deactivate User  ${new_username}  Active users
    END
    IF  ${organisation_only}
      User List Does Not Contain Users From The Other Organisation
    END
  END
  No-Click Logout

Menu Does Not Contain Item
  [Arguments]  ${item}
  Get Text  //*[@id="main-nav"]  not contains  ${item}

User List Does Not Contain Users From The Other Organisation
  Click Access Control
  Get Element Count  //div[@data-e2e-name="tabs-gebruikers-content-1"]//th[contains(.,'_TO1_')]  should be  0

Verify Permissions On Super Admin
  [Arguments]  ${role}  ${update}
  Login Admin With Role  ${role}
  IF  ${update}
    Click Access Control
    Select Access Control Tab  Active admins
    ${username} =  Get User Name Of User By Org  Test Org 1
    Select User  ${username}  Active admins
    Get Element States  id=disable_user_form_submit  contains  attached
  ELSE IF  not ${update}
    Click Access Control
    Access Control Tab Should Not Be Visible  Active admins
    Access Control Tab Should Not Be Visible  Deactivated admins
  END
  No-Click Logout

Verify Permissions On Departments
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Login Admin With Role  ${role}
  IF  not ${read}
    Menu Does Not Contain Item  Bestuursorganen
  ELSE
    IF  ${create} and ${update}
      Click Departments
      ${short_tag} =  Create New Department
      Update Department  ${short_tag}
    END
  END
  No-Click Logout

Verify Permissions On Department Landingpages
  [Arguments]  ${role}  ${update}  ${organisation_only}
  Login Admin With Role  ${role}
  IF  not ${update}
    Menu Does Not Contain Item  Bestuursorganen
  ELSE
    Click Departments
    IF  ${organisation_only}
      Get Element Count  //*[@data-e2e-name="departments-table"]/tbody/tr  should be  2
      Click Edit Department Landingpage  E2E-DEP1
      Click Submit Department Landingpage
    ELSE
      Get Element Count  //*[@data-e2e-name="departments-table"]/tbody/tr  should not be  1
      Update Department
      ...  short_tag=E2E-DEP1
      ...  updated_name=E2E Test Department 1
      ...  updated_short_tag=E2E-DEP1
      ...  updated_slug=e2edep1
      ...  visible_public=${TRUE}
    END
  END
  No-Click Logout

Verify Permissions On Subjects
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Login Admin With Role  ${role}
  IF  not ${read}
    Menu Does Not Contain Item  Onderwerpen
  ELSE
    IF  ${create} and ${update}
      Click Subjects
      ${name} =  Create New Subject
      Update Subject  ${name}
    END
  END
  No-Click Logout

Verify Permissions On Organisations
  [Arguments]  ${role}  ${create}  ${read}  ${update}
  Login Admin With Role  ${role}
  IF  not ${read}
    Organisation Selector Should Not Be Available
  ELSE
    IF  ${create} and ${update}
      ${prefix}  ${_} =  Create New Organisation
      Update Organisation  ${prefix}
    END
  END
  No-Click Logout
  # Delete wordt niet gebruikt, wel in matrix
