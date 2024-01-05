*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String
Resource    keywords.resource

Force Tags      E2E
Test Setup      Open Browser and BaseUrl

* Variables *
${base_url}   https://${acc_user}:${acc_password}@web.acc.woo.rdobeheer.nl
${acc_user}       %{USERNAME_WOO_STAGING}
${acc_password}   %{PASSWORD_WOO_STAGING}

*** Test Cases ***
Basic search
    [Documentation]  Do a basic search and check if it returns results
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=50 documenten in
    Get Text   //body  *=  notulen
    
Basic search and check URL
    [Documentation]  Do a basic search and check if the URL contains the search term
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=50 documenten in
    ${url}  Get Url
    Should Contain  ${url}   search?q=notulen
    Get Text   //body  *=  notulen

Search returns search suggestions
    [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
    Search For  SEARCH_TERM=documanten  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten
    ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    Should Contain  ${suggestSearchElement}   documenten
    Get Text   //body  *=  We konden geen documenten vinden die passen bij uw zoekopdracht "documanten".
    Get Text   //body  *=  Bedoelde u misschien een van de volgende zoektermen: [ documenten ]

Besluitdossier overview page
    [Documentation]  Locate a existing besluitdossier, check if the predefined metadata are available, the numbers match and expected documents are shown
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021  
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Get Text   //body  *=  Gepubliceerd op: 29 augustus 2023
    Get Text   //body  *=  Besluitnummer: VWS-561-2639
    Get Text   //body  *=  Een Woo-verzoek is een verzoek om toegang tot overheidsinformatie in Nederland volgens de Wet Open Overheid. Een Wob-verzoek is een officieel verzoek aan een overheidsinstantie om specifieke informatie of documenten openbaar te maken volgens de Wet openbaarheid van bestuur.
    Get Text   //body  *=  De minister van Volksgezondheid, Welzijn en Sport heeft op 25 april 2023 een besluit genomen op verzoeken in het kader van de Wet open overheid. Het besluit betreft openbaarmaking van documenten die betrekking hebben op informatie aangaande Overleg VWS over de periode mei 2021.
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  Download besluit (881.67 KB)    
    Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text   //body  *=  Mei 2021
    Get Text   //body  *=  29 augustus 2023
    Get Text   //body  *=  Woo-verzoek
    Get Text   //body  *=  792 documenten, 1.815 pagina's Inventarislijst
    #Search the first document visible on the first page
    Get Text   //body  *=  701061
    Get Text   //body  *=  Dagstart bespreeklijst Toegangstesten.docx.docx
    Get Text   //body  *=  14 mei 2021
    #Search the last document visible on the first page
    Get Text   //body  *=  702386
    Get Text   //body  *=  Resultaten Fieldlab Evenementen.docx
    Get Text   //body  *=  14 mei 2021
    #Navigate to the fourth and last page
    Click  xpath=//*[@id="tab1"]/div/nav/ul/li[5]/a
    #Search the first document visible on the last page
    Get Text   //body  *=  704428
    Get Text   //body  *=  RE: MCC notitie testen reizigers - versie voor MCC
    Get Text   //body  *=  31 mei 2021
    #Search the last document visible on the last page
    Get Text   //body  *=  752554
    Get Text   //body  *=  20210525 Powerpoint TCO.pptx.pdf
    Get Text   //body  *=  25 mei 2021
    # Navigate to the documents that are not made public
    Click  xpath=//*[@id="tabcontrol-2"]
    #Search the first document visible on the first page
    Sleep    3s
    Get Text   //body  *=  701104
    Get Text   //body  *=  Aanbieding_111e_OMT-advies_en_toezeggingen_wetgevingsoverleg_quarantaineplicht.doc.doc
    Get Text   //body  *=  7 mei 2021
    #Search the last document visible on the first page
    Get Text   //body  *=  701773
    Get Text   //body  *=  4. Herstelopgaven DOC-19 11 mei 2021.pptx
    Get Text   //body  *=  10 mei 2021
    #Navigate to the fifth and last page
    Click  xpath=//*[@id="tab2"]/div/nav/ul/li[6]/a
    #Search the first document visible on the last page
    Get Text   //body  *=  704662
    Get Text   //body  *=  Terugkoppeling Catshuis stap 4
    Get Text   //body  *=  10 mei 2021
    #Search the last document visible on the last page
    Get Text   //body  *=  704837
    Get Text   //body  *=  Vooruitblik week 39.xlsx
    Get Text   //body  *=  27 september 2021

Document overview page (document is made public)
    [Documentation]  Locate a existing document, check if the predefined metadata are available, document can be downloaded.
    #search by documentnumber
    Search For  SEARCH_TERM=701061
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Dagstart bespreeklijst Toegangstesten.docx.docx
    Get Text   //body  *=  14 mei 2021 om 14:52
    Get Text   //body  *=  Documentnummer: VWS-WOO-701061
    Get Text   //body  *=  Klik op een pagina om de PDF (249.7 KB) te openen in je browser
    Get Text   //body  *=  14 mei 2021
    Get Text   //body  *=  Word-document, 4 pagina's
    Get Text   //body  *=  PDF (249.7 KB
    Get Text   //body  *=  701061
    Get Text   //body  *=  Overleg
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  5.1.2e Eerbiediging van de persoonlijke levenssfeer
    Get Text   //body  *=  Kom je in dit document gegevens tegen waarvan je denkt dat deze gelakt hadden moeten worden? Of is het document slecht leesbaar? Laat het ons weten .
    Get Text   //body  *=  Dit document is door juristen beoordeeld en vervolgens deels openbaar gemaakt. Die beoordeling is gedaan omdat iemand het ministerie van Volksgezondheid, Welzijn en Sport gevraagd heeft interne informatie te openbaren. Hieronder meer informatie over dat verzoek en het besluit:
    Get Text   //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text   //body  *=  De minister van Volksgezondheid, Welzijn en Sport heeft op 25 april 2023 een besluit genomen op verzoeken in het kader van de Wet open overheid. Het besluit betreft openbaarmaking van documenten die betrekking hebben op informatie aangaande Overleg VWS over de periode mei 2021.
    Get Text   //body  *=  Mei 2021
    Get Text   //body  *=  Woo-verzoek
    Get Text   //body  *=  29 augustus 2023
    Get Text   //body  *=  792 documenten, 1.815 pagina's Inventarislijst
    ${filename}  Set Variable  "Dagstart bespreeklijst Toegangstesten.docx.docx"
    ${document_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${document_location}
    Click  "Downloaden (PDF 249.7 KB)"
    Wait For    ${dl_promise}
    File Should Exist  ${document_location}
    ${filesize_besluitdossier}  Get File Size  ${document_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    255696
    Remove File  ${document_location}


Document overview page (document is NOT made public)
    [Documentation]  Locate a existing document that is NOT made public, check if the predefined metadata are available
    #open direct url this time
    Go To  ${base_url}/dossier/VWS-561-2639/document/VWS-WOO-701199
    Get Text   //h1  *=  Verslag webinar RIVM en het coronavirus.SLGTSL.docx
    Get Text   //body  *=  6 mei 2021 om 08:36
    Get Text   //body  *=  Documentnummer: VWS-WOO-701199
    Get Text   //body  *=  Tijdens de beoordeling van het Woo-verzoek heeft Ministerie van Volksgezondheid, Welzijn en Sport besloten dit document niet openbaar te maken.
    Get Text   //body  *=  De reden hiervoor is:
    Get Text   //body  *=  5.1.2i Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen
    Get Text   //body  *=  6 mei 2021
    Get Text   //body  *=  Word-document
    Get Text   //body  *=  701199
    Get Text   //body  *=  Overleg
    Get Text   //body  *=  Niet openbaar
    Get Text   //body  *=  5.1.2i Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen
    Get Text   //body  *=  Dit document is door juristen beoordeeld en vervolgens niet openbaar gemaakt. Die beoordeling is gedaan omdat iemand het ministerie van Volksgezondheid, Welzijn en Sport gevraagd heeft interne informatie te openbaren. Hieronder meer informatie over dat verzoek en het besluit:
    Get Text   //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Get Text   //body  *=  Ministerie van Volksgezondheid, Welzijn en Sport
    Get Text   //body  *=  De minister van Volksgezondheid, Welzijn en Sport heeft op 25 april 2023 een besluit genomen op verzoeken in het kader van de Wet open overheid. Het besluit betreft openbaarmaking van documenten die betrekking hebben op informatie aangaande Overleg VWS over de periode mei 2021.
    Get Text   //body  *=  Mei 2021
    Get Text   //body  *=  Woo-verzoek
    Get Text   //body  *=  29 augustus 2023
    Get Text   //body  *=  792 documenten, 1.815 pagina's Inventarislijst
    # Some checks to make sure download options are NOT available
    Get Text   //body  not contains  Klik op een pagina om de PDF
    Get Text   //body  not contains  Downloaden

Download besluitbrief
    [Documentation]  Locate a existing besluitdossier and download and verify the corresponding besluitbrief
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Get Text   //body  *=  Download besluit (881.67 KB)
    ${filename}  Set Variable  "decision-VWS-561-2639.pdf"
    ${besluitbrief_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitbrief_location}
    Click  "Download besluit "
    Wait For    ${dl_promise}
    File Should Exist  ${besluitbrief_location}
    ${filesize_besluitbrief}  Get File Size  ${besluitbrief_location}
    Should Be Equal As Numbers    ${filesize_besluitbrief}    902834
    Remove File  ${besluitbrief_location}
    
Download inventarislijst
    [Documentation]  Locate a existing besluitdossier and download and verify the corresponding inventarislijst
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier. 
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Get Text   //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Get Text   //body  *=  792 documenten, 1.815 pagina's Inventarislijst
    ${filename}  Set Variable  "inventarislijst-VWS-561-2639.xlsx"
    ${inventarislijst_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${inventarislijst_location}
    Click  "Inventarislijst"
    Wait For    ${dl_promise}
    File Should Exist  ${inventarislijst_location}
    ${filesize_inventarislijst}  Get File Size  ${inventarislijst_location}
    Should Be Equal As Numbers    ${filesize_inventarislijst}    47674
    Remove File  ${inventarislijst_location}

Download small besluitdossier
    [Documentation]  Download a small (<30MB) pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Search For  SEARCH_TERM=Besluit op uw Wob-verzoek inzake de financiële steun die het kabinet heeft verleend aan KLM
    ...    SEARCH_RESULTS=Besluit op uw Wob-verzoek inzake de financiële steun die het kabinet heeft verleend aan KLM
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Click  "Downloaden"
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  88
    ${filename}  Get Text  selector=#main-content > section > div > table > tbody > tr > td:nth-child(1) > span > span    
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  "Downloaden (27.46 MB)"
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    28796811
    Remove File  ${besluitdossier_location}

Download large besluitdossier
    [Documentation]  Download a large (>1GB) pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
    Click  xpath=//*[@id="js-search-results"]/ul/li[1]/h3/a
    Click  "Downloaden"
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  3767
    ${filename}  Get Text  selector=#main-content > section > div > table > tbody > tr > td:nth-child(1) > span > span    
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  "Downloaden (5.81 GB)"
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    6238491622
    Remove File  ${besluitdossier_location}

