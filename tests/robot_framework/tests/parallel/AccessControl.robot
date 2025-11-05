*** Comments ***
# robocop: off=too-many-arguments,too-long-keyword


*** Settings ***
Documentation       Tests that focus on the access control within the Balie
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../../resources/AccessControl.resource
Resource            ../../resources/Departments.resource
Resource            ../../resources/Inquiry.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Close Browser
Test Teardown       Run Keyword If Test Failed  No-Click Logout
Test Tags           ci  accesscontrol


*** Variables ***
${EMAIL}        ${EMPTY}
${PASSWORD}     ${EMPTY}
${OTP}          ${EMPTY}


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

Inquiries
  [Template]  Verify Permissions On Inquiries
  # ${role}  ${create}  ${read}  ${administration}  ${dataset}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  # organisation_admin  ${TRUE}  ${TRUE}  ${FALSE}  # Organisation admins cannot read dossiers, so they can't find any to link.
  dossier_admin  ${TRUE}  ${TRUE}  ${FALSE}
  view_access  ${FALSE}  ${TRUE}  ${FALSE}

Dossiers
  [Template]  Verify Permissions On Dossiers
  # ${role}  ${create}  ${read}  ${update}  ${delete}  ${published_dossiers}  ${unpublished_dossiers}  ${administration}
  dossier_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}  ${TRUE}  ${FALSE}
  view_access  ${FALSE}  ${TRUE}  ${FALSE}  ${FALSE}  ${TRUE}  ${TRUE}  ${FALSE}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}

Documents
  [Documentation]  Note this test does need one dossier, so don't run it individually
  [Template]  Verify Permissions On Documents
  # ${role}  ${update}
  dossier_admin  ${TRUE}
  view_access  ${FALSE}
  super_admin  ${TRUE}

Statistics
  [Template]  Verify Permissions On Statistics
  # ${role}  ${read}
  super_admin  ${TRUE}
  organisation_admin  ${TRUE}
  dossier_admin  ${FALSE}
  view_access  ${FALSE}

Elastic
  [Template]  Verify Permissions On Elastic
  # ${role}  ${read}
  super_admin  ${TRUE}
  organisation_admin  ${FALSE}
  dossier_admin  ${FALSE}
  view_access  ${FALSE}

Deactivation Should Logout A User And Prevent Login
  [Documentation]  Deactivate a logged in user and verify that access is immediately revoked.
  Login Admin With Role  organisation_admin
  ${user1} =  Get Current Logged In Username
  ${context1} =  Get Current Context
  New Context
  New Page
  Login Admin With Role  super_admin
  Select Organisation  E2E Test Organisation
  Click Access Control
  Deactivate User  ${user1}  Active users
  Switch Context  ${context1}
  Click Departments
  Verify Page Error  403
  # Now try logging in with the deactivated account
  No-Click Logout
  Go To Admin
  ${email}  ${password}  ${_} =  Get Admin Credential Variables  organisation_admin
  Fill Text  id=inputEmail  ${email}
  Fill Text  id=inputPassword  ${password}
  Click  //*[@data-e2e-name="login"]
  Get Text  //*[@data-e2e-name="login-error"]  contains  Dit account is gedeactiveerd


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

Login Admin With Role
  [Arguments]  ${role}
  ${email}  ${password}  ${otp} =  Get Admin Credential Variables  ${role}
  Login Admin  username=${email}  password=${password}  otp_secret=${otp}

Get Admin Credential Variables
  [Arguments]  ${role}
  RETURN  ${${role}_EMAIL}  ${${role}_PASSWORD}  ${${role}_OTP}

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
      Click Submit Landingpage
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

Verify Permissions On Inquiries
  [Arguments]
  ...  ${role}
  ...  ${create}
  ...  ${read}
  ...  ${administration}
  Login Admin  # as default admin user who may create dossiers
  Select Organisation  organisation=E2E Test Organisation
  Publish Generated Test WooDecision
  Login Admin With Role  ${role}
  IF  ${read} and ${create}
    Select Organisation  organisation=E2E Test Organisation
    Click Inquiries
    Click Manual Inquiry Linking
    Click Manual Woo Decision Linking
    Link Inquiry To Decision  ZAAK-1  ${DOSSIER_REFERENCE}
    Open Inquiry  ZAAK-1
  END
  Go To  %{URL_ADMIN}/admin/inquiry
  IF  not ${administration}
    Verify Page Error  403
  ELSE
    Get Text  //*[@data-e2e-name="page-title"]  should be  Verzoekenbeheer
  END
  No-Click Logout

Verify Permissions On Dossiers
  [Arguments]
  ...  ${role}
  ...  ${create}
  ...  ${read}
  ...  ${update}
  ...  ${delete}
  ...  ${published_dossiers}
  ...  ${unpublished_dossiers}
  ...  ${administration}
  Login Admin With Role  ${role}
  IF  not ${read}
    Menu Does Not Contain Item  Publicaties
  ELSE
    IF  ${create}
      Click Publications
      Select Organisation  organisation=E2E Test Organisation
      Publish Generated Test WooDecision  publication_status=Gepubliceerd
      VAR  ${dossier_reference_published} =  ${DOSSIER_REFERENCE}
      Publish Generated Test WooDecision  publication_status=Concept
      VAR  ${dossier_reference_unpublished} =  ${DOSSIER_REFERENCE}
      IF  ${published_dossiers}
        IF  ${update}
          Can Update A Dossier  ${dossier_reference_published}
        ELSE
          Can Not Update A Dossier  ${dossier_reference_published}
        END
        IF  ${delete}  Can Not Delete A Dossier  ${dossier_reference_published}
      END
      IF  ${unpublished_dossiers}
        IF  ${update}
          Can Update A Dossier  ${dossier_reference_unpublished}
        ELSE
          Can Not Update A Dossier  ${dossier_reference_unpublished}
        END
        IF  ${delete}
          Can Delete A Dossier  ${dossier_reference_unpublished}
        ELSE
          Can Not Delete A Dossier  ${dossier_reference_unpublished}
        END
      END
    END
    Go To  %{URL_ADMIN}/admin/dossiers
    IF  not ${administration}
      Verify Page Error  403
    ELSE
      Get Text  //*[@data-e2e-name="page-title"]  should be  Publicatiebeheer
    END
  END
  No-Click Logout

Can Update A Dossier
  [Arguments]  ${dossier_reference}
  Search For A Publication  ${dossier_reference}
  Get Element States  //a[@data-e2e-name="edit-basic-details"]  contains  attached
  Click Publications

Can Not Update A Dossier
  [Arguments]  ${dossier_reference}
  Search For A Publication  ${dossier_reference}
  Get Element States  //a[@data-e2e-name="edit-basic-details"]  contains  detached
  Click Publications

Can Delete A Dossier
  [Arguments]  ${dossier_reference}
  Search For A Publication  ${dossier_reference}
  Get Element States  //a[@data-e2e-name="delete-dossier-link"]  contains  attached
  Click Publications

Can Not Delete A Dossier
  [Arguments]  ${dossier_reference}
  Search For A Publication  ${dossier_reference}
  Get Text
  ...  //section[@data-e2e-name="danger-zone"]
  ...  contains
  ...  Dit besluit is gepubliceerd en kan daarom niet meer verwijderd worden.
  Click Publications

Verify Permissions On Documents
  [Arguments]
  ...  ${role}
  ...  ${update}
  Login Admin With Role  ${role}
  Select Organisation  organisation=E2E Test Organisation
  Select First Public Dossier
  IF  ${update}
    Click Documents Edit
  ELSE
    Documents Edit Button Should Not Exist
  END
  No-Click Logout

Verify Permissions On Statistics
  [Arguments]  ${role}  ${read}
  Login Admin With Role  ${role}
  Go To  %{URL_ADMIN}/stats
  IF  not ${read}
    Verify Page Error  403
  ELSE
    Get Text  //main[@id="inhoud"]//h1  contains  Statistieken & Monitoring
  END
  No-Click Logout
  # Alles behalve read is used

Verify Permissions On Elastic
  [Arguments]  ${role}  ${read}
  Login Admin With Role  ${role}
  Go To  %{URL_ADMIN}/elastic
  IF  not ${read}
    Verify Page Error  403
  ELSE
    Get Text  //main[@id="inhoud"]//h1  contains  Elasticsearch beheer
  END
  No-Click Logout
  # Alles behalve read is used

Get Current Context
  ${current_context} =  Get Context IDs  ACTIVE  ACTIVE
  RETURN  ${current_context}[0]
