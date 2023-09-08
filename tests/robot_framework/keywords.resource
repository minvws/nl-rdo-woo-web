*** Keywords ***
Cleanup script
    IF  ${RUN_IN_DOCKER}
        ${cleanup_db}  Set Variable  docker-compose exec app bin/console woopie:dev:clean-sheet -u --force
    ELSE
        ${cleanup_db}  Set Variable  php bin/console woopie:dev:clean-sheet -u --force
    END
    Run Process      ${cleanup_db}  shell=True  alias=cleanup
    ${stdout}        ${stderr}      Get Process Result  cleanup  stdout=True  stderr=True
    Should Be Empty  ${stderr}      Error creating admin ${stderr}

Woo Suite Setup
    Cleanup script
    Create Woo Admin User
    Fill Testdata from Fixture
    Run Consume Worker
    First Time Login With Admin
    Open Browser and BaseUrl

Open Browser and BaseUrl
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors", "--lang=nl"]  
    # New Context   locale=nl-NL
    New Page    ${base_url}  


Search For   
    [Arguments]  ${SEARCH_TERM}=  ${SEARCH_RESULTS}=  ${SEARCH_RESULTS2}=  ${SEARCH_RESULTS3}=  ${SEARCH_RESULTS4}=  ${SEARCH_RESULTS5}=  ${SEARCH_RESULTS6}=  ${SEARCH_RESULTS7}=   ${NOT_VISIBLE1}=default
    Go To  ${base_url}/search?q=
    Fill Text   id=search-field  ${SEARCH_TERM}  
    Keyboard Key    press  Enter
    Get Text   //*[@id="main-content"]  *=  ${SEARCH_RESULTS}  
    Get Text   //body  *=  ${SEARCH_RESULTS2}
    Get Text   //body  *=  ${SEARCH_RESULTS3}
    Get Text   //body  *=  ${SEARCH_RESULTS4}
    Get Text   //body  *=  ${SEARCH_RESULTS5}
    Get Text   //body  *=  ${SEARCH_RESULTS6}
    Get Text   //body  *=  ${SEARCH_RESULTS7}
    IF  "${NOT_VISIBLE1}" != "default"
        Get Text   //*[@id="main-content"]/div  not contains   ${NOT_VISIBLE1}
    END
    

Create Woo Admin User
    IF  ${RUN_IN_DOCKER}
        ${make_user_command}  Set Variable  docker-compose exec app bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
    ELSE
        ${make_user_command}  Set Variable  php bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
    END
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
    IF  ${RUN_IN_DOCKER}
        ${insert_testdata}  Set Variable  docker-compose exec app php bin/console woopie:load:fixture tests/Fixtures/001-inquiry.json
    ELSE
        ${insert_testdata}  Set Variable  php bin/console woopie:load:fixture tests/Fixtures/001-inquiry.json
    END
    Run Process      ${insert_testdata}  shell=True  alias=add_data
    ${stdout}        ${stderr}      Get Process Result  add_data  stdout=True  stderr=True
    Should Be Empty  ${stderr}      Error adding data ${stderr}

Run Consume Worker
    IF  ${RUN_IN_DOCKER}
        ${consume_worker}  Set Variable  docker-compose exec app make consume    
    ELSE
        ${consume_worker}  Set Variable  php bin/console woopie:load:fixture tests/Fixtures/001-inquiry.json
    END
    Start Process      ${consume_worker}  shell=True  alias=add_data
    # ${stdout}        ${stderr}      Get Process Result  add_data  stdout=True  stderr=True
    # Should Be Empty  ${stderr}      Error adding data ${stderr}

First Time Login With Admin
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}/balie/login
    Fill Text   id=inputEmail        ${chosen_email}
    Fill Text   id=inputPassword     ${ww}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=_auth_code         ${otp}
    Click       " Controleren "
    Go To    ${base_url}/balie/login  #nodig omdat redirect niet werkt, werkt bij joshua zonder docker wel
    Fill Text   id=change_password_current_password        ${ww}
    Fill Text   id=change_password_plainPassword_first        ${chosen_password}
    Fill Text   id=change_password_plainPassword_second       ${chosen_password}
    Click       " Wachtwoord aanpassen "
    Get Text   //body  *=  Uitloggen
    Click       " Uitloggen "
    Close Page    CURRENT

Login With Admin
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}/balie/login
    Fill Text   id=inputEmail        ${chosen_email}
    Fill Text   id=inputPassword     ${chosen_password}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=_auth_code         ${otp}
    Click       " Controleren "
    Go To    ${base_url}/balie/login  #nodig omdat redirect niet werkt, werkt bij joshua zonder docker wel
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
    Get Text   //body  *=  Alle besluitdossiers
    Take Screenshot    fullPage=True
    Click      "Nieuw besluitdossier aanmaken"
    Get Text   //body  *=  Nieuw besluitdossier aanmaken
    Fill Text   id=dossier_title         Robot_Dossier_Titel
    Fill Text   id=dossier_summary         Robot_Dossier_Omschrijving
    Select Options By    id=dossier_departments    text    Ministerie van Algemene Zaken
    Select Options By    id=dossier_governmentofficials    text    Minister-President Mark Rutte
    Select Options By    id=dossier_documentPrefix    text    ROBOTPREFIXTITEL
    Select Options By    id=dossier_publication_reason    text    Woo-verzoek
    Select Options By    id=dossier_decision    text    Openbaar
    Type Text  id=dossier_date_from    2/2/2019
    Type Text  id=dossier_date_to    2/2/2020
    Sleep  2s
    Upload File By Selector   id=dossier_inventory   tests/robot_framework/files/sample.xlsx
    # ${promise}=    Promise To Upload File    tests/robot_framework/files/sample.xlsx
    # Click          id=dossier_inventory
    # ${upload_result}=    Wait For    ${promise}
    Take Screenshot    fullPage=True
    Wait Until Network Is Idle  timeout=10s
    Sleep  2s
    Click      "Opslaan"
    Sleep  2s
    Wait Until Network Is Idle  timeout=10s
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot_Dossier_Titel
    Get Console Log    full=True

Woo Suite Teardown
    ${BROWSER_LOGS}     Close Page
    Close Browser
    Log         ${BROWSER_LOGS}
    ${LOG_FILE}  List Directory  storage/logs   *.log  True
    IF  ${LOG_FILE}
        ${LOGS}     Get File    var/log/dev.log
        Log     ${LOGS}
        Copy File           var/log/dev.log    ${OUTPUT DIR}
    END
