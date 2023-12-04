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
#Todo
#    Cleanup-script toevoegen aan begin suite  DONE
#    document prefix cleanup toevoegen aan cleanup script
#Todo Testcases
#    Public portal
#        H) Zoeken op zoekterm
#        H) Zoekresultatenscherm filteren
#        H) Besluit inzien & Documenten downloaden
#        H) Documenten inzien & downloaden
#    Document management
#        M) Besluitenoverzicht tonen
#        H) Besluitdossier toevoegen complete flow
#        H) Besluitdossier bewerken
#        M) Besluitdetail pagina
#        M) Besluit Documenten pagina
#        M) Documenten detail pagina
#        M) Metadata pagina
#        H) Gebruiker beheer/toevoegen


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
    # Get Text   //body  *=  Inloggen   # inloggen knop is verwijderd

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

Login Balie and attempt to create a new besluitdossier
    Login Balie
    #Aanmaken nieuw dossier
    Click  "Nieuw besluit (dossier) aanmaken"
    Get Text   //body  *=  Nieuw besluitdossier - basisgegevens
    Get Text   //body  *=  Houd het kort (maar minimaal 2 karakters) en wees concreet. Gebruik dus geen onnodige voorzetsels, lidwoorden en vanzelfsprekende inhoudelijke woorden zoals Woo, besluit etc.
    ${current_time}    Get Time
    Fill Text   id=details_title         Robot ${current_time}
    Select Options By    id=details_date_from    value    2021-12-01T00:00:00+00:00
    Select Options By    id=details_date_to    value    2023-01-31T00:00:00+00:00
    Select Options By    id=details_departments    index  0
    Get Checkbox State    id=details_publication_reason_1    ==    checked
    Check Checkbox    id=details_publication_reason_0
    Get Checkbox State    id=details_publication_reason_1    ==    unchecked
    Select Options By    id=details_default_subjects    text    Testen
    Select Options By    id=details_documentPrefix_documentPrefix    text    ROBOTPREFIX (Robotprefix)
    ${current_epoch}  Get Time  format=epoch
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
    Get Text   //body  *=  De inventarislijst mist de kolom matter
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
    Get Text   //body  *=  Uploaden gelukt: Alle documenten op de inventarislijst zijn geüpload.
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
    Click  "Vervang inventarislijst"
    Upload File By Selector   id=inventory_inventory   tests/Fixtures/000-inventory-002.xlsx
    Click  "Upload inventarislijst"
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

Search for non specific document 
    Search For  SEARCH_TERM=niet_bestaande_document  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten

Search for word or a partial phrase of the dossier title text  
    Search For  SEARCH_TERM=Test dossier 3 for inquiries  
    # ...    SEARCH_RESULTS=29 documenten in 4 besluiten     
    ...    SEARCH_RESULTS2=Test dossier 3 for inquiries

Search for word or a partial phrase of the dossier summary text
    Search For  SEARCH_TERM=Betreffende het nieuwsbericht ‘4 op de 5 COVID-19 patiënten op de IC is niet gevaccineerd  
    ...    SEARCH_RESULTS=4 op de 5 COVID-19 patiënten op de IC is niet gevaccineerd    
    ...    SEARCH_RESULTS2=Test dossier 3 for inquiries

Search for word or a partial phrase of the page contents
    Search For  SEARCH_TERM=Het begon allemaal op een kalme woensdagochtend in september 
    # ...    SEARCH_RESULTS=12 documenten in 2 besluit  
    ...    SEARCH_RESULTS2=Test dossier 3 for inquiries
    ...    SEARCH_RESULTS3=Het begon allemaal op een kalme woensdagochtend in

Search for phrase that spans two pages
    Search For  SEARCH_TERM=Haar hond, Max over een verdwaalde poes.  
    ...    SEARCH_RESULTS=1 document in 1 besluit   
    ...    SEARCH_RESULTS2=Haar hond, Max,
    ...    SEARCH_RESULTS3=een verdwaalde poes.   

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

Search for published dossier status should be possible
    # check if published status is visible
    Search For  SEARCH_TERM=published dossier
    ...    SEARCH_RESULTS=published dossier 

Search for concept dossier status should NOT be possible
    # check if concept status is visible
    Search For  SEARCH_TERM=Concept dossier 
    ...    NOT_VISIBLE1=Concept dossier

Search for preview dossier status should NOT be possible    
    # check if preview status is visible
    Search For  SEARCH_TERM=Preview dossier
    ...    NOT_VISIBLE1=Preview dossier 

Search for retracted dossier status should NOT be possible   
    # check if retracted status is visible
    Search For  SEARCH_TERM=Retracted dossier
    ...    NOT_VISIBLE1=Retracted dossier

Search for document any status inside concept dossier should NOT be possible 
    #document has any status (in this case suspended=true), but dossier has 'concept' status. This particular document shouldn't be visible in the search results
    Search For  SEARCH_TERM=document-1.pdf
    ...    NOT_VISIBLE1=Concept dossier

Search for document any status inside completed dossier should NOT be possible 
    #document has any status (in this case suspended=true), but dossier has 'completed' status. This particular document shouldn't be visible in the search results
    Search For  SEARCH_TERM=document-1.pdf
    ...    NOT_VISIBLE1=Completed dossier

Search for document empty status inside preview dossier should NOT be possible 
    #document has 'empty' status, but dossier has 'preview' status. This particular document shouldn't be visible in the search results
    Search For  SEARCH_TERM=document-1.pdf
    ...    NOT_VISIBLE1=Preview dossier

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
