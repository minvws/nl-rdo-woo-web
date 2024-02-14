*** Keywords ***
Cleanup script
    IF  ${RUN_IN_DOCKER}
        ${cleanup_db}  Set Variable  docker-compose exec app bin/console woopie:dev:clean-sheet --force
        Run Process      ${cleanup_db}  shell=True  alias=cleanup
    ELSE
        ${cleanup_db}  Set Variable  php bin/console woopie:dev:clean-sheet -u --force
        Run Process      ${cleanup_db}  shell=True  alias=cleanup
        ${stdout}        ${stderr}      Get Process Result  cleanup  stdout=True  stderr=True
        Should Be Empty  ${stderr}      Error creating admin ${stderr}
    END

Create Woo Admin User
    IF  ${RUN_IN_DOCKER}
        ${make_user_command}  Set Variable  docker-compose exec app bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
    ELSE
        ${make_user_command}  Set Variable  php bin/console woopie:user:create "${chosen_email}" "full name" --super-admin
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
    END


Woo Suite Setup
    IF  ${RUN_IN_DOCKER}
        Cleanup script
        Open Browser and BaseUrl
        Setting suite variables
        Login Balie and create a new besluitdossier
    ELSE
        #Cleanup script
        Create Woo Admin User
        #Fill Testdata from Fixture
        #Run Consume Worker
        First Time Login With Admin
        Open Browser and BaseUrl
        Setting suite variables
        Login Balie and create a new besluitdossier
    END

Open Browser and BaseUrl
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors", "--lang=nl"]  #slowMo=0:00:01
    New Context   locale=nl-NL  acceptDownloads=True  viewport={'width': 1920, 'height': 1080}
    New Page    ${base_url}

Setting suite variables
    ${current_time}=  Get Time  format=%Y-%m-%d %H:%M:%S
    Set Suite Variable  ${current_time}
    Log  Current time is: ${current_time}
    ${current_epoch}=  Get Time  format=epoch
    Set Suite Variable  ${current_epoch}
    Log  Current time is: ${current_epoch}
    ${yyyy}  ${mm}  ${dd}=  Get Time  year,month,day
    ${current_date}=    Catenate    SEPARATOR=-    ${yyyy}    ${mm}    ${dd}
    Set Suite Variable  ${current_date}
    Log  Current time is: ${current_date}


Login Balie and create a new besluitdossier
    [Documentation]  Login to Balie and create a besluitdossier and check if it's available on the portal
    Login Balie
    #Aanmaken nieuw dossier
    Click  "Nieuw besluit (dossier) aanmaken"
    Get Text   //body  *=  Nieuw besluitdossier - basisgegevens
    Get Text   //body  *=  Houd het kort (maar minimaal 2 karakters) en wees concreet. Gebruik dus geen onnodige voorzetsels, lidwoorden en vanzelfsprekende inhoudelijke woorden zoals Woo, besluit etc.
    Fill Text   id=details_title         Robot ${current_time}
    Select Options By    id=details_date_from    value    2021-12-01T00:00:00+00:00
    Select Options By    id=details_date_to    value    2023-01-31T00:00:00+00:00
    Select Options By    id=details_departments    index  0
    Get Checkbox State    id=details_publication_reason_1    ==    checked
    Check Checkbox    id=details_publication_reason_0
    Get Checkbox State    id=details_publication_reason_1    ==    unchecked
    Select Options By    id=details_default_subjects    text    Testen
    Fill Text   id=details_dossierNr  ${current_epoch}
    Click  "Opslaan en verder"
    #Besluitbrief uploaden
    Get Text   //body  *=  Robot ${current_time}
    Check Checkbox    id=decision_decision_2
    Fill Text   id=decision_summary         Samenvatting voor het besluitdossier Robot ${current_time}
    Upload File By Selector   id=decision_decision_document   tests/robot_framework/files/officiele_besluitbrief.pdf
    Get Text   //body  *=  ${current_date}
    Click  "Opslaan en verder"
    #Ongeldige inventarislijst uploaden en checken op feedback
    Upload File By Selector   id=inventory_inventory   tests/robot_framework/files/ongeldige_inventarislijst.xlsx
    Upload File By Selector   id=inventory_inventory   tests/robot_framework/files/ongeldige_inventarislijst.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Sleep    5s
    Get Text   //body  *=  Het productierapport mist de kolom matter
    #Geldige inventarislijst uploaden
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-001.xlsx
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-001.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Sleep    5s
    Get Text   //body  *=  Nog te uploaden: 19 van 19 documenten.
    #uploaden zip-bestand
    Upload File By Selector   id=upload-area-dossier-files   tests/Fixtures/000-documents-001.7z
    Take Screenshot    fullPage=True
    Sleep  5s
    Get Text   //body  *=  Uploaden gelukt: Alle documenten uit het productierapport zijn geüpload.
    Click  xpath=//*[@data-e2e-name="to-next-step-link"]
    Get Text   //body  *=  Datum feitelijke verstrekking
    Get Text   //body  *=  Datum openbare publicatie
    Click  "Opslaan en klaarzetten"
    Get Text   //body  *=  Robot ${current_time}
    Get Text   //body  *=  December 2021 - januari 2023
    Get Text   //body  *=  officiele_besluitbrief.pdf
    Get Text   //body  *=  19 van 19 bestanden geüpload
    #Controleren op aanwezigheid opgevoerde zaaknummers
    Get Text   //body  *=  11-111
    Get Text   //body  *=  62-487
    Get Text   //body  *=  99-999
    Click  xpath=//*[@data-e2e-name="logout-link"]
    #Controleren aanwezig besluitdossier in de portal
    Sleep    5s
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Take Screenshot    fullPage=True
    Reload
    Take Screenshot    fullPage=True
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten, 19 pagina's
    Get Text  //body   *=  5080
    Get Text  //body   *=  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   *=  20 augustus 2020
    Get Text  //body   *=  5044
    Get Text  //body   *=  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   *=  9 oktober 2020
    Get Text  //body   *=  5146
    Get Text  //body   *=  case-5-attachment-multi-1.pdf
    Get Text  //body   *=  3 augustus 2020



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
    Reload



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
        ${consume_worker}  Set Variable  make consume
        Start Process      ${consume_worker}  shell=True  alias=start_consume_worker
        # ${stdout}        ${stderr}      Get Process Result  start_consume_worker  stdout=True  stderr=True
        # Should Be Empty  ${stderr}      Error adding data ${stderr}
    END


First Time Login With Admin
    New Browser     chromium    headless=${headless}     args=["--ignore-certificate-errors"]
    New Page    ${base_url}/balie/login
    Fill Text   id=inputEmail        ${chosen_email}
    Fill Text   id=inputPassword     ${ww}
    Click       " Inloggen "
    ${otp}      get otp         ${otp_code}
    Fill Text   id=auth-code         ${otp}
    Click       " Controleren "
    Go To    ${base_url}/balie/login  #nodig omdat redirect niet werkt, werkt bij joshua zonder docker wel
    Fill Text   id=change_password_current_password        ${ww}
    Fill Text   id=change_password_plainPassword_first        ${chosen_password}
    Fill Text   id=change_password_plainPassword_second       ${chosen_password}
    Click       " Wachtwoord aanpassen "
    Get Text   //body  *=  Uitloggen
    Click       xpath=//*[@data-e2e-name="logout-link"]
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
    Wait Until Network Is Idle
    Click  "Nieuw besluit (dossier) aanmaken"
    Wait Until Network Is Idle
    Reload
    Wait Until Network Is Idle
    Get Text   //body  *=  Nieuw besluitdossier - basisgegevens
    Get Text   //body  *=  Onderwerp van het besluit
    Get Text   //body  *=  Houd het kort (maar minimaal 2 karakters) en wees concreet. Gebruik dus geen onnodige voorzetsels, lidwoorden en vanzelfsprekende inhoudelijke woorden zoals Woo, besluit etc.
    Fill Text   id=details_title         Robot ${current_time}
    Select Options By    id=details_date_from    value    2021-12-01T00:00:00+00:00
    Select Options By    id=details_date_to    value    2023-01-31T00:00:00+00:00
    Select Options By    id=details_departments    index  0
    Get Checkbox State    id=details_publication_reason_1    ==    checked
    Check Checkbox    id=details_publication_reason_0
    Get Checkbox State    id=details_publication_reason_1    ==    unchecked
    Select Options By    id=details_default_subjects    index    1
    Select Options By    id=details_documentPrefix_documentPrefix    index    1
    Fill Text   id=details_dossierNr  ${current_epoch}
    Click  "Opslaan en verder"
    Get Text   //body  *=  Robot ${current_time}
    Check Checkbox    id=decision_decision_2
    Fill Text   id=decision_summary         Samenvatting voor het besluitdossier Robot ${current_time}
    Upload File By Selector   id=decision_decision_document   tests/robot_framework/files/officiele_besluitbrief.pdf
    Get Text   //body  *=  ${current_date}
    Click  "Opslaan en verder"
    Upload File By Selector   id=inventory_inventory   tests/robot_framework/files/ongeldige_inventarislijst.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Take Screenshot    fullPage=True
    Sleep    15s
    Take Screenshot    fullPage=True
    Reload
    Take Screenshot    fullPage=True
    Sleep    15s
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Het productierapport mist de kolom matter
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-001.xlsx
    Click  xpath=//*[@id="inventory_submit"]
    Sleep    25s
    Get Text   //body  *=  documentnummer 5034 bestaat al in een ander dossier
    Get Text   //body  *=  documentnummer 5118 bestaat al in een ander dossier
    Get Text   //body  *=  documentnummer 5291 bestaat al in een ander dossier
    Take Screenshot    fullPage=True
    Click  "Besluitdossiers"
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Get Text   //body  *=  December 2021 - januari 2023
    Get Text   //body  *=  Openbaar
    Get Text   //body  *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text   //body  *=  officiele_besluitbrief.pdf
    Get Text   //body  *=  Niet opgegeven
    Get Text   //body  *=  0 bestanden geüpload
    Get Text   //body  *=  Maak eerst documenten compleet
    Get Text   //body  *=  Besluit verwijderen
    Click  "Besluitdossiers"


Filter besluitdossier
    [Documentation]  Check the filter functionality in the besluitdossier page in the Balie
    Get Text  //body  *=  Robot ${current_time}
    Filter op Bestuursorgaan en Status  Nvt  Concept
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=   Status: concept
    Filter op Bestuursorgaan en Status  Nvt  Concept
    Filter op Bestuursorgaan en Status  Nvt  Openbaar
    Get Text  //body   not contains  Robot ${current_time}

Delete besluitdossier
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Get Text   //body  *=  Besluit verwijderen
    Click  xpath=//*[@data-e2e-name="delete-dossier-link"]
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


Filter op Bestuursorgaan en Status
    [Arguments]  ${Bestuursorgaan}=Nvt  ${Status}=Nvt
    Click  "Filters"
    IF  $Bestuursorgaan == "Ministerie van Algemene Zaken"
        Click  id=department_0
    END
    IF  $Bestuursorgaan == "Ministerie van Binnenlandse Zaken en Koninkrijksrelaties"
        Check Checkbox    id=department_1
    END
    IF  $Bestuursorgaan == "Ministerie van Buitenlandse Zaken"
        Check Checkbox    id=department_2
    END
    IF  $Bestuursorgaan == "Ministerie van Defensie"
        Check Checkbox    id=department_3
    END
    IF  $Bestuursorgaan == "Ministerie van Economische Zaken en Klimaat"
        Check Checkbox    id=department_4
    END
    IF  $Bestuursorgaan == "Ministerie van Financiën"
        Check Checkbox    id=department_5
    END
    IF  $Bestuursorgaan == "Ministerie van Infrastructuur en Waterstaat"
        Check Checkbox    id=department_6
    END
    IF  $Bestuursorgaan == "Ministerie van Justitie en Veiligheid"
        Check Checkbox    id=department_7
    END
    IF  $Bestuursorgaan == "Ministerie van Landbouw, Natuur en Voedselkwaliteit"
        Check Checkbox    id=department_8
    END
    IF  $Bestuursorgaan == "Ministerie van Onderwijs, Cultuur en Wetenschap"
        Check Checkbox    id=department_9
    END
    IF  $Bestuursorgaan == "Ministerie van Sociale Zaken en Werkgelegenheid"
        Check Checkbox    id=department_10
    END
    IF  $Bestuursorgaan == "Ministerie van Volksgezondheid, Welzijn en Sport"
        Check Checkbox    id=department_11
    END
    IF  $Status == "Concept"
        Click  id=status_0
    END
    IF  $Status == "Publicatie gepland"
        Check Checkbox    id=status_1
    END
    IF  $Status == "Online voor verzoekers"
        Check Checkbox    id=status_2
    END
    IF  $Status == "Openbaar"
        Check Checkbox    id=status_3
    END
    IF  $Status == "Teruggetrokken"
        Check Checkbox    id=status_4
    END
    Click  "Filteren"

Create new user
    [Documentation]  Login to Balie and create a new user
    Login Balie
    Click  "Toegangsbeheer"
    Get Text  //body   *=  Super beheerder
    Click  "Nieuwe gebruiker aanmaken"
    Get Text  //body   *=  Nieuwe gebruiker aanmaken
    Fill Text   id=user_create_form_name  Testgebruiker${current_epoch}
    Check Checkbox    id=user_create_form_roles_4
    Fill Text   id=user_create_form_email  ${current_epoch}@example.org
    ${new_user_email} =  Set Variable  ${current_epoch}@example.org
    Set Suite Variable  ${new_user_email}
    Log  New user password: ${new_user_email}
    Click  "Account aanmaken"
    Get Text  //body   *=  Het account is aangemaakt
    Click  "Download instructies"
    Get Text  //body   *=  Login instructies voor Testgebruiker${current_epoch}
    Get Text  //body   *=  ${current_epoch}@example.org
    ${element}=  Get Element  xpath=//*[@data-e2e-name="user-password"]
    ${new_user_temp_password}=  Get Text  ${element}
    Set Suite Variable  ${new_user_temp_password}
    Log  New user password: ${new_user_temp_password}
    Parse QR
    Click  xpath=//*[@data-e2e-name="logout-link"]


Edit user
    [Documentation]  Login to Balie and edit User
    Login Balie
    Click  "Toegangsbeheer"
    Reload
    Click  "Testgebruiker${current_epoch}"
    Get Text  //body   *=  Testgebruiker${current_epoch}
    Get Text  //body   *=  ${current_epoch}@example.org
    Get Checkbox State    id=user_info_form_roles_4    ==    checked
    Type Text  id=user_info_form_name      Testgebruiker2_${current_epoch}  delay=50 ms  clear=Yes
    Check Checkbox    id=user_info_form_roles_3
    Click  "Opslaan"
    Get Text  //body   *=  De gebruiker is gewijzigd
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Get Text  //body   *=  Testgebruiker2_${current_epoch}
    Get Text  //body   *=  ${current_epoch}@example.org
    Get Checkbox State    id=user_info_form_roles_3    ==    checked
    Get Checkbox State    id=user_info_form_roles_4    ==    checked
    Click  xpath=//*[@data-e2e-name="logout-link"]


Deactivate user
    [Documentation]  Login to Balie and deactivate user
    Login Balie
    Click  "Toegangsbeheer"
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Get Text  //body   *=  Testgebruiker2_${current_epoch}
    Get Text  //body   *=  ${current_epoch}@example.org
    Click  "Account deactiveren"
    Get Text  //body   *=  Account van Testgebruiker2_${current_epoch} is gedeactiveerd.
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Get Text  //body   *=  Deze gebruiker is momenteel gedeactiveerd.
    Click  xpath=//*[@data-e2e-name="logout-link"]

Activate user
    [Documentation]  Login to Balie and activate user
    Login Balie
    Click  "Toegangsbeheer"
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Get Text  //body   *=  Deze gebruiker is momenteel gedeactiveerd.
    Click  "Account activeren"
    Get Text  //body   *=  Account van Testgebruiker2_${current_epoch} is geactiveerd.
    Click  "Testgebruiker2_${current_epoch}"
    Get Text  //body   not contains  Account van Testgebruiker2_${current_epoch} is gedeactiveerd.
    Click  xpath=//*[@data-e2e-name="logout-link"]


Password reset
    [Documentation]  Login to Balie and reset password
    Login Balie
    Click  "Toegangsbeheer"
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Click  xpath=//*[@data-e2e-name="password-reset-button"]
    Get Element States  xpath=//*[@data-e2e-name="password-reset-instructions"]  contains  visible
    Click  "Ja, reset het wachtwoord"
    Get Text  //body   *=  Dit account is bijgewerkt
    Click  "Download instructies"
    Get Text  //body   *=  Login instructies voor Testgebruiker2_${current_epoch}
    Get Text  //body   *=  ${current_epoch}@example.org
    Click  xpath=//*[@data-e2e-name="logout-link"]

2FA reset
    [Documentation]  Login to Balie and reset 2FA
    Login Balie
    Click  "Toegangsbeheer"
    Reload
    Click  "Testgebruiker2_${current_epoch}"
    Click  xpath=//*[@data-e2e-name="2fa-reset-button"]
    Get Text  //body   *=  Resetten van de twee factor code betekent dat we de oude twee factor code voor altijd verwijderen uit ons systeem. Als je een nieuwe twee factor code aanvraagt zorg dan dat je deze op een veilige manier voor jezelf bewaart. Als je op 'Ja' klikt zal je de nieuwe twee factor code ontvangen.
    Click  "Ja, reset de twee factor code"
    Get Text  //body   *=  Dit account is bijgewerkt
    Click  "Download instructies"
    Click  xpath=//*[@data-e2e-name="logout-link"]



Parse QR
    [Documentation]  Parse the QR that is generated after creating a new user
    ${IMG_PATH}  Take Screenshot     qr  id=QR-code  fileType=jpeg
    Sleep  1
    ${QR_STR}  Read QR Img         ${IMG_PATH}
    Should Not Be Empty  ${QR_STR}  msg=No QR code read
    Log  ${QR_STR}
    ${OTP_CODE_NEW_USER}     Get Regexp Matches  ${QR_STR}  [A-Z0-9]{52}
    Set Suite Variable   ${OTP_CODE_NEW_USER}  ${OTP_CODE_NEW_USER}[0]


Login new user
    Go To  ${base_url_balie}
    Fill Text   id=inputEmail        ${new_user_email}
    Fill Text   id=inputPassword     ${new_user_temp_password}
    Click       " Inloggen "
    ${otp}      get otp         ${OTP_CODE_NEW_USER}
    Fill Text   id=auth-code         ${otp}
    Click       " Controleren "
    Get Text   //body  *=  Uitloggen
    Get Text   //body  *=  Testgebruiker${current_epoch}
    Get Text   //body  *=  ${new_user_email}
    Fill Text   id=change_password_current_password     ${new_user_temp_password}
    Fill Text   id=change_password_plainPassword_first        NieuweGebruikerWachtwoord
    Fill Text   id=change_password_plainPassword_second       NieuweGebruikerWachtwoord
    Click       " Wachtwoord aanpassen "
    Get Text   //body  *=  Uitloggen
    Get Text   //body  *=  Testgebruiker${current_epoch}
    #Gebruiker heeft alleen lezen rechten en mag geen toegang hebben tot "Toegangsbeheer" en de mogelijkheid om een (besluit)dossier aan te maken
    Get Text   //body  not contains  Toegangsbeheer
    Get Text   //body  not contains  Nieuw besluit (dossier) aanmaken
    Click       xpath=//*[@data-e2e-name="logout-link"]
