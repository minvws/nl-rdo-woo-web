*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String
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

*** Test Cases ***
SmokeTest
    ${title}=    Get Title
    Should Be Equal    ${title}    Home | OpenVWS

Setting suite variables
    ${current_time}=  Get Time  format=%Y-%m-%d %H:%M:%S
    Set Suite Variable  ${current_time}
    Log  Current time is: ${current_time}
    ${current_epoch}=  Get Time  format=epoch
    Set Suite Variable  ${current_epoch}
    Log  Current time is: ${current_epoch}

#Create dossier
    #Login With Admin
    # Create a new prefix
    # Create a new dossier

Create a prefix
    Login Balie
    Go To  ${base_url}/balie/admin
    Click  "Prefix beheer"
    Get Text    //h1  ==  Prefix beheer
    Click  "Nieuwe prefix"
    Type Text  id=document_prefix_prefix      ROBOTPREFIX
    Select Options By    id=document_prefix_organisation    index  1
    Type Text  id=document_prefix_description      Robotprefix
    Click  "Opslaan"
    #later betere validatie inbouwen
    Click  "Uitloggen"

Login Balie and create a new besluitdossier
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
    Select Options By    id=details_documentPrefix_documentPrefix    text    ROBOTPREFIX (Robotprefix)
    Fill Text   id=details_dossierNr  ${current_epoch}  
    Click  "Opslaan en verder"
    #Besluitbrief uploaden
    Get Text   //body  *=  Robot ${current_time}
    Check Checkbox    id=decision_decision_2
    Fill Text   id=decision_summary         Samenvatting voor het besluitdossier Robot ${current_time}
    Upload File By Selector   id=decision_decision_document   tests/robot_framework/files/officiele_besluitbrief.pdf
    ${yyyy}  ${mm}  ${dd}=  Get Time  year,month,day
    ${current_date}=    Catenate    SEPARATOR=-    ${yyyy}    ${mm}    ${dd}
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
    Upload File By Selector   //input[contains(@id, 'upload-area-')]  tests/Fixtures/000-documents-001.zip
    Sleep    5s
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Uploaden gelukt: Alle documenten uit het productierapport zijn geüpload.
    Click  "Verder naar besluit"
    Get Text   //body  *=  Datum feitelijke verstrekking
    Get Text   //body  *=  Datum openbare publicatie
    Click  "Opslaan en klaarzetten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Uitloggen"
    #Controleren aanwezig besluitdossier in de portal
    Sleep    30s
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Take Screenshot    fullPage=True
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten, 68 pagina's
    Get Text  //body   *=  5080
    Get Text  //body   *=  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   *=  20 augustus 2020
    Get Text  //body   *=  5044
    Get Text  //body   *=  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   *=  9 oktober 2020
    Get Text  //body   *=  5146
    Get Text  //body   *=  case-5-attachment-multi-1.pdf
    Get Text  //body   *=  3 augustus 2020


Besluitdossier overview page
    [Documentation]  Locate a existing besluitdossier, check if the predefined metadata are available, the numbers match and expected documents are shown
    Search For  SEARCH_TERM=Robot ${current_time} 
    ...    SEARCH_RESULTS=Robot ${current_time}
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Robot ${current_time}
    Get Text   //body  *=  Een Woo-verzoek is een verzoek om toegang tot overheidsinformatie in Nederland volgens de Wet Open Overheid. Een Wob-verzoek is een officieel verzoek aan een overheidsinstantie om specifieke informatie of documenten openbaar te maken volgens de Wet openbaarheid van bestuur.
    Get Text   //body  *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text   //body  *=  openbaar
    Get Text   //body  *=  Download besluit (12.95 KB)    
    Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text   //body  *=  December 2021 - januari 2023
    Get Text   //body  *=  Wob-verzoek
    Get Text   //body  *=  19 documenten, 68 pagina's Inventarislijst
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
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  case-5-mail-with-multi-attachment-no-thread.pdf
    Get Text   //body  *=  10 augustus 2020 om 07:28
    Get Text   //body  *=  Documentnummer: ROBOTPREFIX-5144
    Get Text   //body  *=  Klik op een pagina om de PDF (654.76 KB) te openen in je browser
    Get Text   //body  *=  10 augustus 2020
    Get Text   //body  *=  E-mailbericht, 2 pagina's
    Get Text   //body  *=  PDF (654.76 KB)
    Get Text   //body  *=  5144
    Get Text   //body  *=  Overleggen VWS - kern Scenario's en maatregelen Vaccinaties en medicatie
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  5.1.2e Eerbiediging van de persoonlijke levenssfeer
    Get Text   //body  *=  Kom je in dit document gegevens tegen waarvan je denkt dat deze gelakt hadden moeten worden? Of is het document slecht leesbaar? Laat het ons weten .
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
    Get Text   //body  *=  19 documenten, 68 pagina's Inventarislijst
    ${filename}  Set Variable  "case-5-mail-with-multi-attachment-no-thread.pdf"
    ${document_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${document_location}
    Click  "Downloaden (PDF 654.76 KB)"
    Wait For    ${dl_promise}
    File Should Exist  ${document_location}
    ${filesize_besluitdossier}  Get File Size  ${document_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    670475
    Remove File  ${document_location}

Download besluitbrief
    [Documentation]  Locate a existing besluitdossier and download and verify the corresponding besluitbrief
    Search For  SEARCH_TERM=Robot ${current_time} 
    ...    SEARCH_RESULTS=Robot ${current_time}
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
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
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Robot ${current_time}
    Get Text   //body  *=  19 documenten, 68 pagina's Inventarislijst
    ${filename}  Set Variable  "inventarislijst-1702297696.xlsx"
    ${inventarislijst_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${inventarislijst_location}
    Click  "Inventarislijst"
    Wait For    ${dl_promise}
    File Should Exist  ${inventarislijst_location}
    ${filesize_inventarislijst}  Get File Size  ${inventarislijst_location}
    Should Be Equal As Numbers    ${filesize_inventarislijst}    7332
    Remove File  ${inventarislijst_location}


Download besluitdossier
    [Documentation]  Download a pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Click  "Alle gepubliceerde besluiten"
    Click  "Robot ${current_time}"
    Click  "Downloaden"
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  19
    ${filename}  Get Text  selector=#main-content > section > div > table > tbody > tr > td:nth-child(1) > span > span    
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  "Downloaden (10.64 MB)"
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    11156757
    Remove File  ${besluitdossier_location}

Search for non specific document 
    Search For  SEARCH_TERM=niet_bestaande_document  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten

Search for word or a partial phrase of the dossier title text  
    Search For  SEARCH_TERM=Robot ${current_time}
    ...    SEARCH_RESULTS2=Robot ${current_time}

Search for word or a partial phrase of the dossier summary text
    Search For  SEARCH_TERM=het besluitdossier Robot ${current_time}
    ...    SEARCH_RESULTS=Samenvatting voor het besluitdossier Robot  

Filter
    [Documentation]  Filter the existing besluitdossier by daterange
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    #The existing besluitdossier has a period of december 2021 - januari 2023
    #    [------------Besluitdossier------------]
    #    [------Filter--------------------------]
    Type Text  //input[@id='date-from']     30/11/2021 delay=500 ms  clear=Yes
    Click  "Filters"
    Type Text  //input[@id='date-to']     30/11/2021 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Get Text  //body   not contains  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [--------------------------Filter------]
    Type Text  //input[@id='date-from']     01/02/2023 delay=500 ms  clear=Yes
    Click  "Filters"
    Type Text  //input[@id='date-to']     01/02/2023 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Get Text  //body   not contains  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [-------------------------Filter-------]
    Type Text  //input[@id='date-from']     31/01/2023 delay=500 ms  clear=Yes
    Click  "Filters"
    Type Text  //input[@id='date-to']     01/02/2023 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [-------Filter-------------------------]
    Type Text  //input[@id='date-from']     31/01/2021 delay=500 ms  clear=Yes
    Click  "Filters"
    Type Text  //input[@id='date-to']     01/12/2023 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [----------------Filter----------------]
    Type Text  //input[@id='date-from']     01/05/2022 delay=500 ms  clear=Yes
    Click  "Filters"
    Type Text  //input[@id='date-to']     01/06/2022 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    -----------------Filter----------------]
    Click  //*[@id="js-search-results"]/div/div[2]/ul/li[1]/a/i
    Get Text  //body   *=   Datum tot: 1 juni 2022 
    Get Text  //body   *=  Robot ${current_time}
    #    [------------Besluitdossier------------]
    #    [----------------Filter-----------------
    Type Text  //input[@id='date-from']     01/05/2022 delay=500 ms  clear=Yes
    Click  "Filters"
    Sleep  2s
    Click  //*[@id="js-search-results"]/div/div[2]/ul/li[2]/a/i
    Sleep  2s
    Get Text  //body   *=  Robot ${current_time}



# Search for word or a partial phrase of the page contents
#     Search For  SEARCH_TERM=Het begon allemaal op een kalme woensdagochtend in september 
#     # ...    SEARCH_RESULTS=12 documenten in 2 besluit  
#     ...    SEARCH_RESULTS2=Test dossier 3 for inquiries
#     ...    SEARCH_RESULTS3=Het begon allemaal op een kalme woensdagochtend in

# Search for phrase that spans two pages
#     Search For  SEARCH_TERM=Haar hond, Max over een verdwaalde poes.  
#     ...    SEARCH_RESULTS=1 document in 1 besluit   
#     ...    SEARCH_RESULTS2=Haar hond, Max,
#     ...    SEARCH_RESULTS3=een verdwaalde poes.   

# Search and filter documents by daterange
#     Search For  SEARCH_TERM=chatgpt 
#     ...    SEARCH_RESULTS=2 documenten in 2 besluiten   
#     ...    SEARCH_RESULTS2=Test dossier 2 for inquiries
#     ...    SEARCH_RESULTS3=Test dossier 3 for inquiries
#     #...    SEARCH_RESULTS4=Ministerie van Economische Zaken en Klimaat
#     #...    SEARCH_RESULTS5=Ministerie van Volksgezondheid, Welzijn en Sport
#     # ...    SEARCH_RESULTS6=Minister Hugo de Jonge
#     # ...    SEARCH_RESULTS7=Minister Stef Blok
#     # check of alles nog aanwezig is na filter
#     Type Text  //input[@id='date-from']     01/01/2022 delay=1 ms  clear=Yes
#     Click  "Filters"
#     Wait Until Network Is Idle    timeout=3s
#     Get Text   //body  *=  2 documenten in 2 besluiten
#     Get Text   //body  *=  Test dossier 2 for inquiries
#     Get Text   //body  *=  Test dossier 3 for inquiries
#     #Get Element States   id=input_Ministerie%20van%20Economische%20Zaken%20en%20Klimaat  *=  visible
#     #Get Element States    id=input_Ministerie%20van%20Volksgezondheid%2C%20Welzijn%20en%20Sport    *=   visible  
#     # Get Text   //body  *=  Minister Hugo de Jonge
#     # Get Text   //body  *=  Minister Stef Blok
#     Type Text  //input[@id='date-to']     02/02/2022 delay=1ms  clear=Yes
#     Click  "Filters"
#     Wait Until Network Is Idle    timeout=3s
#     Get Text   //body  *=  1 document in 1 besluit
#     #Get Text   //body  *=  Ministerie van Economische Zaken en Klimaat
#     # Get Text   //body  *=  Minister Stef Blok
#     #Wait For Elements State    id=input_Ministerie%20van%20Volksgezondheid%2C%20Welzijn%20en%20Sport    detached    timeout=2 s
#     #Wait For Elements State    id=input_Minister%20Hugo%20de%20Jonge    detached    timeout=2 s
#     # Get Element States    id=input_Ministerie%20van%20Volksgezondheid%2C%20Welzijn%20en%20Sport    *=   detached  
#     # Get Element States   id=input_Minister%20Hugo%20de%20Jonge  *=   detached 
#     Click  //*[@id="js-search-results"]/div/div[2]/ul/li[1]/button  #vanaf filter weg klikken
#     Click  "Filters"
#     #Get Text   //body  *=  Ministerie van Economische Zaken en Klimaat
#     # Get Text   //body  *=  Minister Stef Blok
#     Wait Until Network Is Idle    timeout=3s
#     Get Text   //body  *=  Test dossier 2 for inquiries
#     Get Text   //body  *=  1 document in 1 besluit

# Search and filter documents by department
#     # filter by daterange
#     Search For  SEARCH_TERM=chatgpt 
#     ...    SEARCH_RESULTS=2 documenten in 2 besluiten   
#     ...    SEARCH_RESULTS2=Test dossier 2 for inquiries
#     ...    SEARCH_RESULTS3=Test dossier 3 for inquiries
#     ...    SEARCH_RESULTS4=Ministerie van Economische Zaken en Klimaat
#     ...    SEARCH_RESULTS5=Ministerie van Volksgezondheid, Welzijn en Sport
#     # ...    SEARCH_RESULTS6=Minister Hugo de Jonge
#     # ...    SEARCH_RESULTS7=Minister Stef Blok
#     # check checkbox nog niet helemaal stabiel
#     Sleep  1s
#     Check Checkbox  //*[@id="input_Ministerie%20van%20Economische%20Zaken%20en%20Klimaat"]  force=true
#     Sleep  1s
#     Get Text   //body  *=  1 document in 1 dossier
#     Get Text   //body  *=  Test dossier 2 for inquiries
#     Get Text   //body  *=  Ministerie van Economische Zaken en Klimaat
#     # Get Text   //body  *=  Minister Stef Blok
#     Get Text   //body  *=  Organisatie: Ministerie van Economische Zaken en Klimaat 
#     Click  //*[@id="js-search-results"]/div/div[2]/ul/li/button
#     Get Text   //body  *=  2 documenten in 2 dossiers
#     Get Text   //body  *=  Test dossier 2 for inquiries
#     Get Text   //body  *=  Test dossier 3 for inquiries
#     Get Text   //body  *=  Ministerie van Economische Zaken en Klimaat
#     Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
#     # Get Text   //body  *=  Minister Hugo de Jonge
#     # Get Text   //body  *=  Minister Stef Blok

# Search for published dossier status should be possible
#     # check if published status is visible
#     Search For  SEARCH_TERM=published dossier
#     ...    SEARCH_RESULTS=published dossier 

# Search for concept dossier status should NOT be possible
#     # check if concept status is visible
#     Search For  SEARCH_TERM=Concept dossier 
#     ...    NOT_VISIBLE1=Concept dossier

# Search for preview dossier status should NOT be possible    
#     # check if preview status is visible
#     Search For  SEARCH_TERM=Preview dossier
#     ...    NOT_VISIBLE1=Preview dossier 

# Search for retracted dossier status should NOT be possible   
#     # check if retracted status is visible
#     Search For  SEARCH_TERM=Retracted dossier
#     ...    NOT_VISIBLE1=Retracted dossier

# Search for document any status inside concept dossier should NOT be possible 
#     #document has any status (in this case suspended=true), but dossier has 'concept' status. This particular document shouldn't be visible in the search results
#     Search For  SEARCH_TERM=document-1.pdf
#     ...    NOT_VISIBLE1=Concept dossier

# Search for document any status inside completed dossier should NOT be possible 
#     #document has any status (in this case suspended=true), but dossier has 'completed' status. This particular document shouldn't be visible in the search results
#     Search For  SEARCH_TERM=document-1.pdf
#     ...    NOT_VISIBLE1=Completed dossier

# Search for document empty status inside preview dossier should NOT be possible 
#     #document has 'empty' status, but dossier has 'preview' status. This particular document shouldn't be visible in the search results
#     Search For  SEARCH_TERM=document-1.pdf
#     ...    NOT_VISIBLE1=Preview dossier

# Search for document empty status inside published dossier should be possible
#     #document has 'empty' status, but dossier has 'preview' status. This particular document should be visible in the search results
#     Search For  SEARCH_TERM=document-2.pdf
#     ...    SEARCH_RESULTS=published dossier 


# Search for document suspended status should be possible
#     Search For  SEARCH_TERM=document-6-for-case-55-555.pdf
#     ...    SEARCH_RESULTS=document-6-for-case-55-555.pdf
#     ...    SEARCH_RESULTS2=Test dossier 3 for inquiries 

# Search for document withdrawn status
# withdrawn document wordt nog aan gebouwd > https://github.com/minvws/nl-rdo-woo-web-private/pull/882 
# waarschijnlijk niet mogelijk via fixtures, zal via de balie interface getest moeten worden
    # Search For  SEARCH_TERM=document-7-for-case-11-111-22-222.pdf
    # ...    SEARCH_RESULTS=document-7-for-case-11-111-22-222.pdf


# Check if all document judgement statuses are visible
#     Go to  ${base_url}/browse
#     Get Text   //body  *=  Openbaar
#     Get Text   //body  *=  Reeds openbaar
#     Get Text   //body  *=  Deels openbaar
#     Get Text   //body  *=  Niet openbaar




Login Balie and retract besluitdossier
    #Inloggen balie & start intrekken dossier
    Login Balie
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@id="js-dossier-search-previews"]/div[3]/table/tbody/tr/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Click  xpath=//*[@id="inhoud"]/div/div[2]/section[6]/a
    Click    id=withdraw_form_reason_2
    Fill Text   id=withdraw_form_explanation  Alle documenten in besluitdossier Robot ${current_epoch} worden ingetrokken
    Click  "Intrekken"
    Get Text  //body   *=  Ingetrokken
    Click  "Uitloggen"
    #Controleren of documenten zijn getrokken op de portal
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten, 68 pagina's
    Get Text  //body   not contains  5080
    Get Text  //body   not contains  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   not contains  20 augustus 2020
    Get Text  //body   not contains  5044
    Get Text  //body   not contains  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   not contains  9 oktober 2020
    Get Text  //body   not contains  5146
    Get Text  //body   not contains  case-5-attachment-multi-1.pdf
    Get Text  //body   not contains  3 augustus 2020

Login Balie and replace besluitdossier
    #Inloggen balie & vervangen inventarislijst
    Login Balie
    Type Text  id=search-previews      Robot ${current_time}  delay=50 ms  clear=Yes
    Take Screenshot    fullPage=True
    Click  xpath=//*[@id="js-dossier-search-previews"]/div[3]/table/tbody/tr/td[2]
    Take Screenshot    fullPage=True
    Get Text   //body  *=  Robot ${current_time}
    Click  xpath=//*[@id="inhoud"]/div/div[2]/section[3]/div/a
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
    Get Text  //body   *=  Inventarislijst geüpload en gecontroleerd
    Get Text  //body   *=  19 bestaande documenten worden aangepast.
    Click  "Ok, vervang inventarislijst"
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
    Click  xpath=//*[@id="js-dossier-search-previews"]/div[3]/table/tbody/tr/td[2]
    Get Text  //body   *=  Ingetrokken
    Get Text  //body   *=  5080
    Get Text  //body   *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
    Click  "Document vervangen"
    Upload File By Selector   id=replace_form_document   tests/Fixtures/000-documents-001/5080.pdf
    Click  "Vervang document"
    Get Text  //body   *=  Het document wordt nu verwerkt, dit kan even duren.
    Click  "ROBOTPREFIX-5080"
    Get Text  //body   *=  Gepubliceerd
    Get Text  //body   *=  5080
    Get Text  //body   *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
    Click  "Uitloggen"
    #Controleren of documenten zijn vervangen op de portal
    Click  "Alle gepubliceerde besluiten"
    Get Text   //body  *=  Robot ${current_time}
    Click  "Robot ${current_time}"
    Get Text  //body  *=  Robot ${current_time}
    Get Text  //body   *=  Samenvatting voor het besluitdossier Robot ${current_time}
    Get Text  //body   *=  19 documenten
    Get Text  //body   contains  5080
    Get Text  //body   contains  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body   contains  20 augustus 2020
    Get Text  //body   not contains  5044
    Get Text  //body   not contains  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body   not contains  9 oktober 2020
    Get Text  //body   not contains  5146
    Get Text  //body   not contains  case-5-attachment-multi-1.pdf
    Get Text  //body   not contains  3 augustus 2020
    Take Screenshot    fullPage=True
