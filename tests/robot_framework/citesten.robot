*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String

Force Tags      CI
Suite Setup     Woo Suite Setup
# Suite Teardown  Woo Suite Teardown


* Variables *
${base_url}   localhost:8000
${chosen_email}       email@example.org
${chosen_password}    IkLoopNooitVastVandaag

*** Test Cases ***
SmokeTest
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}
     ${title}=    Get Title
    Should Be Equal    ${title}    Home | Woo Platform
    Get Text   //body  *=  Inloggen
    [Teardown]    Close Browser    CURRENT

Create dossier
    Login With Admin
    Create a new prefix
    Create a new dossier

*** Keywords ***
Woo Suite Setup
    Create Woo Admin User
    Fill Testdata from Fixture
    First Time Login With Admin

Create Woo Admin User
    #${make_user_command}  Set Variable  docker-compose exec app bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
    ${make_user_command}  Set Variable  bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
    Run Process      ${make_user_command}  shell=True  alias=create_admin
    ${stdout}        ${stderr}      Get Process Result  create_admin  stdout=True  stderr=True
    Should Be Empty  ${stderr}      Error creating admin ${stderr}
    ${regel_ww}      Get Line      ${stdout}      1
    ${ww}      Get Substring      ${regel_ww}      13
    ${otp_line}      Get Line      ${stdout}      3
    ${otp_code}      Get Substring      ${otp_line}      13
    Should Not Be Empty  ${otp_code}  No otp code found in: ${stdout}
    Set Suite Variable   ${otp_code}  ${otp_code}
    Set Suite Variable   ${ww}  ${ww}

Fill Testdata from Fixture
    #${fixture_command}  Set Variable  docker-compose exec app php bin/console woopie:load:fixture tests/Fixtures/001-inquiry.json
    ${fixture_command}  Set Variable  php bin/console woopie:load:fixture tests/Fixtures/001-inquiry.json
    Run Process      ${fixture_command}  shell=True  alias=add_data
    ${stdout}        ${stderr}      Get Process Result  add_data  stdout=True  stderr=True
    Should Be Empty  ${stderr}      Error adding data ${stderr}


First Time Login With Admin
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}/login
    Fill Text   id=inputEmail        ${chosen_email}
    Fill Text   id=inputPassword     ${ww}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=_auth_code         ${otp}
    Click       " Inloggen "
    Fill Text   id=change_password_current_password        ${ww}
    Fill Text   id=change_password_plainPassword_first        ${chosen_password}
    Fill Text   id=change_password_plainPassword_second       ${chosen_password}
    Click       " Wachtwoord aanpassen "
    Get Text   //body  *=  Uitloggen
    Click       " Uitloggen "
    Close Page    CURRENT

Login With Admin
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}/login
    Fill Text   id=inputEmail        ${chosen_email}
    Fill Text   id=inputPassword     ${chosen_password}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=_auth_code         ${otp}
    Click       " Inloggen "
    Get Text   //body  *=  Uitloggen

Create a new prefix
    Click       " Counter"
    Click        "Prefix beheer"
    Get Text   //body  *=  Prefix beheer
    Click      "Nieuwe prefix"
    Get Text   //body  *=  Nieuwe prefix aanmaken
    Fill Text   id=document_prefix_prefix         RobotPrefixTitel
    Fill Text   id=document_prefix_description         Robot_Prefix_Omschrijving
    Click       "Opslaan"
    Get Text   //body  *=  ROBOTPREFIXTITEL
    Get Text   //body  *=  Robot_Prefix_Omschrijving

Create a new dossier
    Click       " Counter"
    Click      "Dossier management"
    Get Text   //body  *=  Dossier management
    Take Screenshot    fullPage=True
    Click      "Nieuw dossier"
    Get Text   //body  *=  Nieuw dossier aanmaken
    Fill Text   id=dossier_title         Robot_Dossier_Titel
    Fill Text   id=dossier_summary         Robot_Dossier_Omschrijving
    Select Options By    id=dossier_departments    text    Ministerie van Algemene Zaken
    Select Options By    id=dossier_governmentofficials    text    Minister-President Mark Rutte
    Select Options By    id=dossier_documentPrefix    text    ROBOTPREFIXTITEL
    Select Options By    id=dossier_publication_reason    text    Woo: verzoek
    Select Options By    id=dossier_decision    text    Openbaar
    Type Text  id=dossier_date_from    2/2/2019
    Type Text  id=dossier_date_to    2/2/2020
    Sleep  2s
    #Upload File By Selector   id=dossier_inventory   tests/robot_framework/files/sample.xlsx
    ${promise}=    Promise To Upload File    tests/robot_framework/files/sample.xlsx
    Click          id=dossier_inventory
    ${upload_result}=    Wait For    ${promise}
    Take Screenshot    fullPage=True
    Wait Until Network Is Idle  timeout=10s
    Sleep  2s
    Click      "Opslaan"
    Sleep  2s
    Wait Until Network Is Idle  timeout=10s
    Get Text   //body  *=   Dossier has been created successfully
    Take Screenshot    fullPage=True
    Get Console Log    full=True

Woo Suite Teardown
    ${BROWSER_LOGS}     Close Page
    Close Browser
    Log         ${BROWSER_LOGS}
    ${LOG_FILE}  List Directory  storage/logs   *.log  True
    IF  ${LOG_FILE}
        ${LARAVEL_LOGS}     Get File    storage/logs/laravel.log
        Log     ${LARAVEL_LOGS}
        Copy File           storage/logs/laravel.log    ${OUTPUT DIR}
    END
