*** Comments ***
# robocop: off=too-many-arguments,too-long-keyword


*** Settings ***
Documentation       Dossier/inquiry/admin-page access control tests, split out of AccessControl.robot
...                 to keep individual pabot suites balanced. "Documents" needs a dossier that
...                 "Dossiers" creates, so those two stay together and in order in this file.
...                 Doesn't create the fixed-name "Test Org 1" organisation used by
...                 AccessControl.robot's "Super Admin" test -- not needed here, and creating it
...                 from two suites at once would race.
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
  Create Test User  organisation=E2E Test Organisation  role=super_admin
  Create Test User  organisation=E2E Test Organisation  role=organisation_admin
  Create Test User  organisation=E2E Test Organisation  role=dossier_admin
  Create Test User  organisation=E2E Test Organisation  role=view_access

Menu Does Not Contain Item
  [Arguments]  ${item}
  Get Text  //*[@id="main-nav"]  not contains  ${item}

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
  Go To  ${URL_ADMIN}/admin/inquiry
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
    Go To  ${URL_ADMIN}/admin/dossiers
    IF  not ${administration}
      Verify Page Error  403
    ELSE
      Get Text  //*[@data-e2e-name="page-title"]  should be  Publicatiebeheer
    END
  END
  No-Click Logout

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
  Go To  ${URL_ADMIN}/stats
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
  Go To  ${URL_ADMIN}/elastic
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
