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
    #Cleanup script
    #Create Woo Admin User
    #Fill Testdata from Fixture
    #Run Consume Worker
    #First Time Login With Admin
    Open Browser and BaseUrl

Open Browser and BaseUrl
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors", "--lang=nl"]  #slowMo=0:00:01
    New Context   locale=nl-NL  acceptDownloads=True  
    New Page    ${base_url}  


Search For   
    [Arguments]  ${SEARCH_TERM}=  ${SEARCH_RESULTS}=  ${SEARCH_RESULTS2}=  ${SEARCH_RESULTS3}=  ${SEARCH_RESULTS4}=  ${SEARCH_RESULTS5}=  ${SEARCH_RESULTS6}=  ${SEARCH_RESULTS7}=   ${NOT_VISIBLE1}=default
    Go To  ${base_url}
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

Login Balie
    Go To  ${base_url_balie}
    Fill Text   id=inputEmail        ${tst_balie_user}
    Fill Text   id=inputPassword     ${tst_balie_password}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=auth-code         ${otp}
    Click       " Controleren "
    Get Text   //body  *=  Uitloggen

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


Create nieuw besluit(dossier)
    Click  "Nieuw besluit (dossier) aanmaken"
    Get Text   //body  *=  Nieuw besluitdossier - basisgegevens
    Get Text   //body  *=  Onderwerp van het besluit
    Get Text   //body  *=  Houd het kort (maar minimaal 2 karakters) en wees concreet. Gebruik dus geen onnodige voorzetsels, lidwoorden en vanzelfsprekende inhoudelijke woorden zoals Woo, besluit etc.
    ${current_time}    Get Time
    Fill Text   id=details_title         Robot ${current_time}
    Select Options By    id=details_date_from    value    2021-12-01T00:00:00+00:00
    Select Options By    id=details_date_to    value    2023-01-31T00:00:00+00:00
    Select Options By    id=details_departments    index  0
    # ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    # ${details_departments}    Get Element    #details_departments
    # ${details_departments}  Set Variable  Get Element Attribute    id:details_departments    index=1
    #details_departments
    #Select Options By    id=details_departments    text    Ministerie van Volksgezondheid, Welzijn en Sport (minVWS)
    #Select Options By    id=details_governmentofficials    index    1
    Get Checkbox State    id=details_publication_reason_1    ==    checked
    Check Checkbox    id=details_publication_reason_0
    Get Checkbox State    id=details_publication_reason_1    ==    unchecked
    #Select Options By    id=details_default_subjects    text    Testen
    Select Options By    id=details_default_subjects    index    1
    #Select Options By    id=details_documentPrefix_documentPrefix    text    TEST1
    Select Options By    id=details_documentPrefix_documentPrefix    index    1
    ${current_epoch}  Get Time  format=epoch
    Fill Text   id=details_dossierNr  ${current_epoch}  
    # Click  "Concept opslaan"
    Click  "Opslaan en verder"
    Get Text   //body  *=  Robot ${current_time}
    Check Checkbox    id=decision_decision_2
    Fill Text   id=decision_summary         Samenvatting voor het besluitdossier Robot ${current_time}
    Upload File By Selector   id=decision_decision_document   tests/robot_framework/files/officiele_besluitbrief.pdf
    #${current_date}    Get Time  format=year/month
    ${yyyy}  ${mm}  ${dd}=  Get Time  year,month,day
    ${current_date}=    Catenate    SEPARATOR=-    ${yyyy}    ${mm}    ${dd}
    Get Text   //body  *=  ${current_date}
    # Click  "Concept opslaan"
    Click  "Opslaan en verder"
    Upload File By Selector   id=inventory_inventory   tests/robot_framework/files/ongeldige_inventarislijst.xlsx
    Upload File By Selector   id=inventory_inventory   tests/robot_framework/files/ongeldige_inventarislijst.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Sleep    5s
    Get Text   //body  *=  Het productierapport mist de kolom matter
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-001.xlsx
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-001.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Sleep    5s
    Get Text   //body  *=  documentnummer 5034 bestaat al in een ander dossier
    Get Text   //body  *=  documentnummer 5118 bestaat al in een ander dossier
    Get Text   //body  *=  documentnummer 5291 bestaat al in een ander dossier
    Take Screenshot    fullPage=True
    Click  "Besluitdossiers"
    #Fill Text   id=search-previews         Robot ${current_time}
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@id="js-dossier-search-previews"]/div[3]/table/tbody/tr/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Get Text   //body  *=  December 2021 - januari 2023
    #todo checken verantwoordelijk bestuursorgaan
    Get Text   //body  *=  Openbaar
    Get Text   //body  *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text   //body  *=  officiele_besluitbrief.pdf
    #todo datum besluit
    Get Text   //body  *=  Niet opgegeven
    Get Text   //body  *=  0 bestanden geüpload
    Get Text   //body  *=  Maak eerst documenten compleet
    Get Text   //body  *=  Besluit verwijderen
    Click  xpath=//*[@id="inhoud"]/div/div[2]/section[6]/a
    Get Text   //body  *=  Bevestig de verwijdering van dit dossier. Kan niet ongedaan gemaakt worden!
    Click  xpath=//*[@id="delete_form_submit"]
    Get Text   //body  *=  Het dossier wordt verwijderd, het kan even duren voor dit verwerkt is.
    Sleep  30s
    Click  "Naar overzicht besluitdossiers"
    Log     Robot ${current_time} 
    Get Text  //body   not contains  Robot ${current_time}





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
