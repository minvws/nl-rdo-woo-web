*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String
Library    ${CURDIR}/libraries/QR.py
Resource    keywords.resource

Force Tags      CI
Suite Setup     Woo Suite Setup
# Suite Teardown  Woo Suite Teardown

* Variables *
${RUN_IN_DOCKER}           False
${base_url}                localhost:8000
${base_url_balie}          localhost:8000/balie/dossiers
${chosen_email}            email@example.org
${chosen_password}         IkLoopNooitVastVandaag
${tst_balie_user}          email@example.org
${tst_balie_password}      IkLoopNooitVastVandaag


*** Test Cases ***

Login Balie and filter besluitdossier
    [Documentation]  Check the filter functionality in the besluitdossier page in the Balie
    Login Balie
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  Openbaar
    Get Text  //body  *=  MINVWS
    Filter op Bestuursorgaan en Status  Ministerie van Algemene Zaken  Nvt
    Get Text  //body  *=  Bestuursorgaan: Ministerie van Algemene Zaken
    Get Text  //body  *=  Er zijn geen besluiten gevonden die aan de filters voldoen.
    Filter op Bestuursorgaan en Status  Ministerie van Algemene Zaken  Nvt
    Filter op Bestuursorgaan en Status  Ministerie van Volksgezondheid, Welzijn en Sport  Nvt
    Get Text  //body  *=   Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  Openbaar
    Get Text  //body  *=  MINVWS
    Filter op Bestuursorgaan en Status  Nvt  Concept
    Get Text  //body  *=   Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text  //body  *=   Status: Concept
    Get Text  //body  *=  Er zijn geen besluiten gevonden die aan de filters voldoen.
    Filter op Bestuursorgaan en Status  Nvt  Concept
    Filter op Bestuursorgaan en Status  Nvt  Openbaar
    Get Text  //body  *=   Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=   Status: Openbaar
    Get Text  //body  *=  Openbaar
    Get Text  //body  *=  MINVWS
    Click  xpath=//*[@data-e2e-name="logout-link"]

Besluitdossier overview page
    [Documentation]  Locate a existing besluitdossier, check if the predefined metadata are available, the numbers match and expected documents are shown
    Search For  SEARCH_TERM=Robot ${current_time}
    ...    SEARCH_RESULTS=Robot ${current_time}
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Get Text   //h1  *=  Robot ${current_time}
    Get Text   //body  *=  Een Woo-verzoek is een verzoek om toegang tot overheidsinformatie in Nederland volgens de Wet Open Overheid. Een Wob-verzoek is een officieel verzoek aan een overheidsinstantie om specifieke informatie of documenten openbaar te maken volgens de Wet openbaarheid van bestuur.
    Get Text   //body  *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text   //body  *=  openbaar
    Get Text   //body  *=  Download besluit (12.95 KB)
    Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text   //body  *=  December 2021 - januari 2023
    Get Text   //body  *=  Wob-verzoek
    Get Text   //body  *=  19 documenten, 19 pagina's Inventarislijst
    #Search the first document visible on the first page
    Get Text   //body  *=  5062
    Get Text   //body  *=  case-2-email-with-more-emails-in-thread-4.pdf
    Get Text   //body  *=  9 oktober 2020
    #Search the last document visible on the first page
    Get Text   //body  *=  5036
    Get Text   //body  *=  case-2-email-with-more-emails-in-thread-1.pdf
    Get Text   //body  *=  11 augustus 2020

Document overview page (document is made public)
    [Documentation]  Locate a existing document, check if the predefined metadata are available, document can be downloaded.
    Search For  SEARCH_TERM=case-5-mail-with-multi-attachment-no-thread.pdf
    ...    SEARCH_RESULTS=case-5-mail-with-multi-attachment-no-thread.pdf
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    #Click  "case-5-mail-with-multi-attachment-no-thread.pdf"
    Get Text   //h1  *=  case-5-mail-with-multi-attachment-no-thread.pdf
    Get Text   //body  *=  10 augustus 2020
    Get Text   //body  *=  Documentnummer: MINVWS-4-5144
    Get Text   //body  *=  Klik op een pagina om de PDF (12.95 KB) te openen in je browser
    Get Text   //body  *=  10 augustus 2020
    Get Text   //body  *=  E-mailbericht, 1 pagina's
    Get Text   //body  *=  PDF (12.95 KB)
    Get Text   //body  *=  5144
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  5.1.2e Eerbiediging van de persoonlijke levenssfeer
    Get Text   //body  *=  Kom je in dit document gegevens tegen waarvan je denkt dat deze gelakt hadden moeten worden? Of is het document slecht leesbaar? Laat het ons weten.
    Get Text   //body  *=  Bijlagen bij dit e-mailbericht
    Get Text   //body  *=  5146
    Get Text   //body  *=  case-5-attachment-multi-1.pdf
    Get Text   //body  *=  5148
    Get Text   //body  *=  5167
    Get Text   //body  *=  3 augustus 2020
    Get Text   //body  *=  17 augustus 2020
    Get Text   //body  *=  21 augustus 2020
    Get Text   //body  *=  Dit document is door juristen beoordeeld en vervolgens deels openbaar gemaakt. Die beoordeling is gedaan omdat iemand het ministerie van Volksgezondheid, Welzijn en Sport gevraagd heeft interne informatie te openbaren. Hieronder meer informatie over dat verzoek en het besluit:
    Get Text   //body  *=  Robot ${current_time}
    Get Text   //body  *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text   //body  *=  December 2021 - januari 2023
    Get Text   //body  *=  Wob-verzoek
    Get Text   //body  *=  19 documenten, 19 pagina's Inventarislijst
    ${filename}  Set Variable  "case-5-mail-with-multi-attachment-no-thread.pdf"
    ${document_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${document_location}
    Click  "Downloaden (PDF 12.95 KB)"
    Wait For    ${dl_promise}
    File Should Exist  ${document_location}
    ${filesize_besluitdossier}  Get File Size  ${document_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    13264
    Remove File  ${document_location}

Download besluitbrief
    [Documentation]  Locate a existing besluitdossier and download and verify the corresponding besluitbrief
    Search For  SEARCH_TERM=Robot ${current_time}
    ...    SEARCH_RESULTS=Robot ${current_time}
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Get Text   //h1  *=  Robot ${current_time}
    Get Text   //body  *=  Download besluit (12.95 KB)
    ${filename}  Set Variable  "decision-1702211972.pdf"
    ${besluitbrief_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitbrief_location}
    Click  "Download besluit "
    Wait For    ${dl_promise}
    File Should Exist  ${besluitbrief_location}
    ${filesize_besluitbrief}  Get File Size  ${besluitbrief_location}
    Should Be Equal As Numbers    ${filesize_besluitbrief}    13264
    Remove File  ${besluitbrief_location}

Download inventarislijst
    [Documentation]  Locate a existing besluitdossier and download and verify the corresponding inventarislijst
    Search For  SEARCH_TERM=Robot ${current_time}
    ...    SEARCH_RESULTS=Robot ${current_time}
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Get Text   //h1  *=  Robot ${current_time}
    Get Text   //body  *=  19 documenten, 19 pagina's Inventarislijst
    ${filename}  Set Variable  "inventarislijst-1702297696.xlsx"
    ${inventarislijst_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${inventarislijst_location}
    Click  "Inventarislijst"
    Wait For    ${dl_promise}
    File Should Exist  ${inventarislijst_location}
    ${filesize_inventarislijst}  Get File Size  ${inventarislijst_location}
    #afrondingsissue. Robuste oplossing voor verzinnen
    #Should Be Equal As Numbers    ${filesize_inventarislijst}    7361
    Remove File  ${inventarislijst_location}


Download besluitdossier
    [Documentation]  Download a pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Click  "Alle gepubliceerde besluiten"
    Click  "Robot ${current_time}"
    Sleep  30s
    Click  xpath=//*[@data-e2e-name="download-documents-button"]
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  19
    ${filename}  Get Text  xpath=//*[@data-e2e-name="file-name"]
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  xpath=//*[@data-e2e-name="download-file-link"]
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    #afrondingsissue. Robuste oplossing voor verzinnen
    #Should Be Equal As Numbers    ${filesize_besluitdossier}    11156757
    Remove File  ${besluitdossier_location}

Search for non existing document
    Search For  SEARCH_TERM=niet_bestaande_document
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten

Search for word or a partial phrase of the dossier title text
    Search For  SEARCH_TERM=Robot ${current_time}
    ...    SEARCH_RESULTS2=Robot ${current_time}

Search for word or a partial phrase of the dossier summary text
    Search For  SEARCH_TERM=het besluitdossier Robot ${current_time}
    ...    SEARCH_RESULTS=Samenvatting voor het besluitdossier Robot


Filter besluitdossiers
    [Documentation]  Filter the existing besluitdossier by daterange
    #Set Suite Variable  ${current_time}  2023-12-28 18:40:09
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    #The existing besluitdossier has a period of december 2021 - januari 2023
    #    [------------Besluitdossier------------]
    #    [------Filter--------------------------]
    #Date-from 30/11/2021
    #Date-to 30/11/2021
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-11-30&dt%5Bto%5D=2021-11-30
    Take Screenshot    fullPage=True
    Get Text  //body   not contains  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [--------------------------Filter------]
    #Date-from 01/02/2023
    #Date-to 01/02/2023
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2023-02-01&dt%5Bto%5D=2023-02-01
    Take Screenshot    fullPage=True
    Get Text  //body   not contains  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [-------------------------Filter-------]
    #Date-from 31/01/2023
    #Date-to 01/02/2023
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2023-01-31&dt%5Bto%5D=2023-02-01
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [-------Filter-------------------------]
    #Date-from 31/01/2021
    #Date-to 01/12/2023
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-01-31&dt%5Bto%5D=2023-12-01
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [----------------Filter----------------]
    #Date-from 01/05/2022
    #Date-to 01/06/2022
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2022-05-01&dt%5Bto%5D=2022-06-01
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    -----------------Filter----------------]
    #Date-from -
    #Date-to 01/06/2022
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bto%5D=2022-06-01
    Get Text  //body   *=   Datum tot: 1 juni 2022
    Get Text  //body   *=  Robot ${current_time}
    Take Screenshot    fullPage=True
    #    [------------Besluitdossier------------]
    #    [----------------Filter-----------------
    #Date-from 01/05/2022
    #Date-to -
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2022-05-01
    Get Text  //body   *=  Robot ${current_time}
    Take Screenshot    fullPage=True

Link Zaaknummer to a besluitdossier
    [Documentation]  Login to Balie and link a Zaaknummer to a Besluitdossier
    Login Balie
    Click  "Zaken"
    Get Text   //body  *=  11-111
    Get Text   //body  *=  62-487
    Get Text   //body  *=  99-999
    Get Text   //body  *=  99-999
    Sleep  2s
    Click  "11-111"
    Get Text   //body  *=  Besluiten en documenten voor zaaknummer 11-111
    Get Text   //body  *=  Download de inventarislijst zaaknummer 11-111
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  6 documenten met zaaknummer 11-111 in dit besluit
    Get Text  //body  *=  case-5-mail-with-multi-attachment-no-thread.pdf
    Get Text  //body  *=  case-2-email-with-more-emails-in-thread-1.pdf
    Go Back
    Click  "Zaaknummer aan besluiten of documenten koppelen"
    Click  "Koppel aan besluiten"
    Fill Text   id=inquiry_link_dossier_form_map  33-66-99
    Click  "+ Kies besluit..."
    Type Text  id=link-dossiers-search-input      Robot ${current_time}  delay=50 ms  clear=Yes
    Click    xpath=//li[contains(@id, 'option-')]
    Click   xpath=//*[@id="js-link-dossier"]
    Get Text  //body  *=  Robot ${current_time}
    Click  xpath=//*[@id="inquiry_link_dossier_form_link"]
    Sleep  10s
    Reload
    Get Text   //body  *=  33-66-99
    Click  "33-66-99"
    Get Text   //body  *=  Besluiten en documenten voor zaaknummer 33-66-99
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  Download de inventarislijst zaaknummer 33-66-99
    Get Text  //body  *=  1 besluitdossier(s) toegevoegd
    Go Back
    Click  xpath=//*[@data-e2e-name="logout-link"]



Link Zaaknummer to documents
    [Documentation]  Login to Balie and link a Zaaknummer to documents
    Login Balie
    Click  "Zaken"
    Get Text   //body  *=  11-111
    Get Text   //body  *=  62-487
    Get Text   //body  *=  99-999
    Sleep  2s
    Click  "11-111"
    Get Text   //body  *=  Besluiten en documenten voor zaaknummer 11-111
    Get Text   //body  *=  Download de inventarislijst zaaknummer 11-111
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  6 documenten met zaaknummer 11-111 in dit besluit
    Get Text  //body  *=  case-5-mail-with-multi-attachment-no-thread.pdf
    Get Text  //body  *=  case-2-email-with-more-emails-in-thread-1.pdf
    Go Back
    Click  "Zaaknummer aan besluiten of documenten koppelen"
    Click  "Koppel aan documenten"
    Upload File By Selector   xpath=//*[@id="inquiry_link_documents_form_upload"]   tests/robot_framework/files/koppel_zaaknummer.xlsx
    Click  "Koppelen"
    Get Text  //body  *=  De zaaknummers worden gekoppeld
    Sleep  10s
    Reload
    Get Text  //body  *=  44-444
    Click  "44-444"
    Get Text   //body  *=  Besluiten en documenten voor zaaknummer 44-444
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  Download de inventarislijst zaaknummer 44-444
    Get Text  //body  *=  1 besluitdossier(s) toegevoegd
    Get Text  //body  *=  1 document(en) toegevoegd
    Sleep  10s
    Click  "Downloaden"
    Get Text   //body  *=  Download zaak archief
    Get Text   //body  *=  Het archief is gereed voor download
    ${filename}  Get Text  xpath=//*[@data-e2e-name="file-name"]
    ${zaakarchief_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${zaakarchief_location}
    Click  xpath=//*[@data-e2e-name="download-file-link"]
    Wait For    ${dl_promise}
    File Should Exist  ${zaakarchief_location}
    ${filesize_zaakarchief}  Get File Size  ${zaakarchief_location}
    Remove File  ${zaakarchief_location}
    Go Back
    Go Back
    Click  xpath=//*[@data-e2e-name="logout-link"]


Retract a besluitdossier
    [Documentation]  Login to Balie and retract the existing besluitdossier
    #Inloggen balie & start intrekken dossier
    Login Balie
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Click  xpath=//*[@data-e2e-name="withdraw-documents-link"]
    Click    id=withdraw_form_reason_2
    Fill Text   id=withdraw_form_explanation  Alle documenten in besluitdossier Robot ${current_epoch} worden ingetrokken
    Click  "Intrekken"
    Get Text  //body   *=  Ingetrokken
    Click  xpath=//*[@data-e2e-name="logout-link"]
    #Controleren of documenten zijn getrokken op de portal
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Reload
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten, 19 pagina's
    Get Text  //body   not contains  5080
    Get Text  //body   not contains  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   not contains  20 augustus 2020
    Get Text  //body   not contains  5044
    Get Text  //body   not contains  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   not contains  9 oktober 2020
    Get Text  //body   not contains  5146
    Get Text  //body   not contains  case-5-attachment-multi-1.pdf
    Get Text  //body   not contains  3 augustus 2020

Replace besluitdossier
    [Documentation]  Login to Balie and replace the existing productierapport and some documents
    Login Balie
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Click  xpath=//*[@data-e2e-name="documents-section"]//*[@data-e2e-name="edit-link"]
    Get Text  //body   *=  5080
    Get Text  //body   *=  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   *=  5044
    Get Text  //body   *=  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   *=  5146
    Get Text  //body   *=  case-5-attachment-multi-1.pdf
    Click  "Vervang productierapport"
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-002.xlsx
    Click  "Upload productierapport"
    Sleep    5s
    Get Text  //body   *=  Productierapport geüpload en gecontroleerd
    Get Text  //body   *=  19 bestaande documenten worden aangepast.
    Click  "Ok, vervang productierapport"
    Sleep    5s
    Get Text  //body   *=  De inventaris is succesvol vervangen.
    #Vervangen documenten
    Click  "Naar documenten"
    Get Text  //body   *=  5080
    Get Text  //body   *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   *=  5044
    Get Text  //body   *=  vervangen_case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   *=  5146
    Get Text  //body   *=  vervangen_case-5-attachment-multi-1.pdf
    Type Text  id=search-previews      vervangen_case-4-mail-with-attachment-thread-1.pdf  delay=50 ms  clear=Yes
    Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]
    Get Text  //body   *=  5080
    Get Text  //body   *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
    Click  "Document vervangen"
    Upload File By Selector   id=replace_form_document   tests/Fixtures/000-documents-001/5080.pdf
    Click  "Vervang document"
    Get Text  //body   *=  Het document wordt nu verwerkt, dit kan even duren.
    Click  "MINVWS-4-5080"
    Get Text  //body   *=  Gepubliceerd
    Get Text  //body   *=  5080
    Get Text  //body   *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
    Click  "Zaken"
    Get Text   //body  *=  11-111
    Get Text   //body  *=  22-222
    Get Text   //body  *=  62-487
    Get Text   //body  *=  99-999
    Reload
    Click  "22-222"
    Get Text   //body  *=  Besluiten en documenten voor zaaknummer 22-222
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body  *=  Download de inventarislijst zaaknummer 22-222
    Get Text  //body  *=  1 besluitdossier(s) toegevoegd
    Get Text  //body  *=  6 document(en) toegevoegd
    Go Back
    Click  xpath=//*[@data-e2e-name="logout-link"]
    #Controleren of documenten zijn vervangen op de portal
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten


Filter search results
    [Documentation]  Check the filter results
    Go To  ${base_url}/search?q=
    # Filter by "Type bronbestand"
    Get Checkbox State    id=input_pdf    ==    unchecked
    Check Checkbox    id=input_pdf
    Get Checkbox State    id=input_pdf    ==    checked
    Get Text  //body   *=  vervangen_case-7-attachment-for-non-existing-email.pdf
    Get Text  //body   *=   Type bronbestand: PDF
    # Filter by "Onderwerp"
    Get Checkbox State    id=input_Testen    ==    unchecked
    Check Checkbox    id=input_Testen
    Get Checkbox State    id=input_Testen    ==    checked
    Get Text  //body   *=  vervangen_case-7-attachment-for-non-existing-email.pdf
    Get Text  //body   *=   Onderwerp: Testen
    # Filter by "Soort besluit"
    Get Checkbox State    id=input_partial_public    ==    unchecked
    Check Checkbox    id=input_partial_public
    Get Checkbox State    id=input_partial_public    ==    checked
    Get Text  //body   *=  vervangen_case-7-attachment-for-non-existing-email.pdf
    Get Text  //body   *=  Soort besluit: Deels openbaar
    # Filter by "Uitzonderingsgrond"
    Get Checkbox State    id=input_5.1.2i    ==    unchecked
    Check Checkbox    id=input_5.1.2i
    Get Checkbox State    id=input_5.1.2i    ==    checked
    Get Text  //body   *=  vervangen_case-7-attachment-for-non-existing-email.pdf
    Get Text  //body   *=   Uitzonderingsgrond: 5.1.2i Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen
    Take Screenshot    fullPage=True

User management
    [Documentation]  Login to Balie and check the user management functionality
    Create new user
    Login new user
    Edit user
    Deactivate user
    Activate user
    Password reset
    2FA reset
