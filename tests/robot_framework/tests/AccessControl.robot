*** Comments ***
# robocop: off=too-many-arguments


*** Settings ***
Documentation       Tests that focus on the access control within the Balie
Library             String
Library             Browser
Library             DebugLibrary
Library             OTP
Resource            ../resources/Setup.resource
Resource            ../resources/AccessControl.resource
Resource            ../resources/Departments.resource
Resource            ../resources/Subjects.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Inquiry.resource
Suite Setup         Suite Setup
Test Teardown       Run Keyword If Test Failed  No-Click Logout
Test Timeout        5 minutes  # For yet unknown reasons, this testsuite sometimes runs endlessly until the Github job timeout is reached, ruining the whole testrun. Therefore this suite has a test timeout of 5 minutes.
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
  global_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}
  dossier_admin  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}
  view_access  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}

Departments
  [Template]  Verify Permissions On Departments
  # ${role}  ${create}  ${read}  ${update}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  organisation_admin  ${FALSE}  ${TRUE}  ${FALSE}
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

Inquiries
  [Template]  Verify Permissions On Inquiries
  # ${role}  ${create}  ${read}  ${administration}  ${dataset}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}
  # organisation_admin  ${TRUE}  ${TRUE}  ${FALSE}  # Organisation admins cannot read dossiers, so they can't find any to link.
  global_admin  ${TRUE}  ${TRUE}  ${FALSE}
  dossier_admin  ${TRUE}  ${TRUE}  ${FALSE}
  view_access  ${FALSE}  ${TRUE}  ${FALSE}

Dossiers
  [Template]  Verify Permissions On Dossiers
  # ${role}  ${create}  ${read}  ${update}  ${delete}  ${published_dossiers}  ${unpublished_dossiers}  ${administration}
  dossier_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}  ${TRUE}  ${FALSE}
  view_access  ${FALSE}  ${TRUE}  ${FALSE}  ${FALSE}  ${TRUE}  ${TRUE}  ${FALSE}
  super_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}
  global_admin  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${TRUE}  ${FALSE}  ${FALSE}
  organisation_admin  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}  ${FALSE}

Documents
  [Documentation]  Note this test does need one dossier, so don't run it individually
  [Template]  Verify Permissions On Documents
  # ${role}  ${update}
  dossier_admin  ${TRUE}
  view_access  ${FALSE}
  super_admin  ${TRUE}
  global_admin  ${TRUE}

Statistics
  [Template]  Verify Permissions On Statistics
  # ${role}  ${read}
  super_admin  ${TRUE}
  organisation_admin  ${TRUE}
  global_admin  ${TRUE}
  dossier_admin  ${FALSE}
  view_access  ${FALSE}

Elastic
  [Template]  Verify Permissions On Elastic
  # ${role}  ${read}
  super_admin  ${TRUE}
  organisation_admin  ${FALSE}
  global_admin  ${FALSE}
  dossier_admin  ${FALSE}
  view_access  ${FALSE}


*** Keywords ***
Suite Setup
  Cleansheet
  Suite Setup Generic
  Login Admin
  Create New Organisation  Test Org 1  ministerie van Algemene Zaken  TESTORG1
  Create Test User  organisation=Programmadirectie Openbaarheid  role=super_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=global_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=organisation_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=dossier_admin
  Create Test User  organisation=Programmadirectie Openbaarheid  role=view_access

Verify Permissions On Users
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
  No-Click Logout

Menu Does Not Contain Item
  [Arguments]  ${item}
  Get Text  //*[@id="main-nav"]  not contains  ${item}

User List Does Not Contain Users From The Other Organisation
  Click Access Control
  Get Element Count  //div[@data-e2e-name="tabs-gebruikers-content-1"]//th[contains(.,'_TO1_')]  should be  0

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
  No-Click Logout

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
  No-Click Logout

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
  No-Click Logout
  # Delete wordt niet gebruikt, wel in matrix

Verify Permissions On Inquiries
  [Arguments]
  ...  ${role}
  ...  ${create}
  ...  ${read}
  ...  ${administration}
  Set Credentials By Role  ${role}
  Cleansheet
  Login Admin  # as default admin user who may create dossiers
  Select Organisation  organisation=Programmadirectie Openbaarheid
  Publish Test WooDecision
  ...  production_report=tests/robot_framework/files/inquiries/productierapport2.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten2.zip
  ...  number_of_documents=3
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  ${read} and ${create}
    Select Organisation  organisation=Programmadirectie Openbaarheid
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
    Get Text  //nav//h1  contains  Zaak
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
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  IF  not ${read}
    Menu Does Not Contain Item  Publicaties
  ELSE
    IF  ${create}
      Cleansheet
      Click Publications
      Select Organisation  organisation=Programmadirectie Openbaarheid
      Publish Test WooDecision
      ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 openbaar.xlsx
      ...  documents=tests/robot_framework/files/woodecision/documenten - 2.zip
      ...  number_of_documents=2
      ...  publication_status=Gepubliceerd
      VAR  ${dossier_reference_published}  ${DOSSIER_REFERENCE}
      Publish Test WooDecision
      ...  production_report=tests/robot_framework/files/woodecision/productierapport - 2 andere.xlsx
      ...  documents=tests/robot_framework/files/woodecision/documenten - 2 andere.zip
      ...  number_of_documents=2
      ...  publication_status=Concept
      VAR  ${dossier_reference_unpublished}  ${DOSSIER_REFERENCE}
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
      Get Text  //nav//h1  contains  Publicatie
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
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  Select Organisation  organisation=Programmadirectie Openbaarheid
  Select Openbaar Dossier
  IF  ${update}
    Click Documents Edit
  ELSE
    Documents Edit Button Should Not Exist
  END
  No-Click Logout

Verify Permissions On Statistics
  [Arguments]  ${role}  ${read}
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
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
  Set Credentials By Role  ${role}
  Login Admin  username=${EMAIL}  password=${PASSWORD}  otp_secret=${OTP}
  Go To  %{URL_ADMIN}/elastic
  IF  not ${read}
    Verify Page Error  403
  ELSE
    Get Text  //main[@id="inhoud"]//h1  contains  Elasticsearch beheer
  END
  No-Click Logout
  # Alles behalve read is used
