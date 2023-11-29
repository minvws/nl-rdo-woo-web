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
${RUN_IN_DOCKER}   False
${base_url}   localhost:8000
${chosen_email}       email@example.org
${chosen_password}    IkLoopNooitVastVandaag

*** Test Cases ***
SmokeTest
    ${title}=    Get Title
    Should Be Equal    ${title}    Home | OpenVWS
    # Get Text   //body  *=  Inloggen   # inloggen knop is verwijderd

#Create dossier
    #Login With Admin
    # Create a new prefix
    # Create a new dossier

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

Search for document empty status inside published dossier should be possible
    #document has 'empty' status, but dossier has 'preview' status. This particular document should be visible in the search results
    Search For  SEARCH_TERM=document-2.pdf
    ...    SEARCH_RESULTS=published dossier 


Search for document suspended status should be possible
    Search For  SEARCH_TERM=document-6-for-case-55-555.pdf
    ...    SEARCH_RESULTS=document-6-for-case-55-555.pdf
    ...    SEARCH_RESULTS2=Test dossier 3 for inquiries 

# Search for document withdrawn status
# withdrawn document wordt nog aan gebouwd > https://github.com/minvws/nl-rdo-woo-web-private/pull/882 
# waarschijnlijk niet mogelijk via fixtures, zal via de balie interface getest moeten worden
    # Search For  SEARCH_TERM=document-7-for-case-11-111-22-222.pdf
    # ...    SEARCH_RESULTS=document-7-for-case-11-111-22-222.pdf


Check if all document judgement statuses are visible
    Go to  ${base_url}/browse
    Get Text   //body  *=  Openbaar
    Get Text   //body  *=  Reeds openbaar
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  Niet openbaar
