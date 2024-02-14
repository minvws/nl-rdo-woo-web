*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String
Resource    keywords.resource

Force Tags      E2E_ACC
Test Setup      Open Browser and BaseUrl

* Variables *
${base_url}   https://${acc_user}:${acc_password}@web.acc.woo.rdobeheer.nl
${acc_user}       %{USERNAME_WOO_STAGING}
${acc_password}   %{PASSWORD_WOO_STAGING}

*** Test Cases ***
Basic search
    [Documentation]  Do a basic search and check if it returns results
    Search For  SEARCH_TERM=notulen
    ...    SEARCH_RESULTS=notulen
    Get Text   //body  *=  notulen

Basic search and check URL
    [Documentation]  Do a basic search and check if the URL contains the search term
    Search For  SEARCH_TERM=notulen
    ...    SEARCH_RESULTS=notulen
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
    Get Text   //body  *=  Bedoelde u misschien een van de volgende zoektermen: documenten

Besluitdossier overview page
    [Documentation]  Locate a existing besluitdossier, check if the predefined metadata are available, the numbers match and expected documents are shown
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
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
    Get Text   //body  *=  701841
    Get Text   //body  *=  4.1. Notitie - Verdelingssystematiek Toegangstesten.pdf
    Get Text   //body  *=  25 mei 2021
    #Navigate to the fourth and last page
    Click  xpath=//*[@data-e2e-name="tab1"]//*[@data-e2e-name="page-number-5"]
    #Search the first document visible on the last page
    Get Text   //body  *=  704722
    Get Text   //body  *=  Verslag MT MEVA 20210504.pdf
    Get Text   //body  *=  12 mei 2021
    #Search the last document visible on the last page
    Get Text   //body  *=  752554
    Get Text   //body  *=  20210525 Powerpoint TCO.pptx.pdf
    Get Text   //body  *=  25 mei 2021
    # Navigate to the documents that are not made public
    Click  xpath=//*[@data-e2e-name="tab-button-2"]
    Sleep  1s
    Click  xpath=//*[@data-e2e-name="tab-button-2"]
    #Search the first document visible on the first page
    Sleep    3s
    Get Text   //body  *=  701117
    Get Text   //body  *=  Annotatie tbv ACC - DCC - 25 mei 2021.docx.docx
    Get Text   //body  *=  24 mei 2021
    #Search the last document visible on the first page
    Get Text   //body  *=  701803
    Get Text   //body  *=  2. Concept verslag DOC-19 18 mei 2021.docx
    Get Text   //body  *=  21 mei 2021
    #Navigate to the fifth and last page
    Click  xpath=//*[@data-e2e-name="tab2"]//*[@data-e2e-name="page-number-4"]
    #Search the first document visible on the last page
    Get Text   //body  *=  704016
    Get Text   //body  *=  Geannoteerde agenda ACC-19 6 mei 2021 agendapunt 7 Voortgangsrapportagearchivering hotspot Covid-19 (stuk bijgevoegd DGSC-19).docx
    Get Text   //body  *=  6 mei 2021
    #Search the last document visible on the last page
    Get Text   //body  *=  704837
    Get Text   //body  *=  Vooruitblik week 39.xlsx
    Get Text   //body  *=  27 september 2021

Document overview page (document is made public)
    [Documentation]  Locate a existing document, check if the predefined metadata are available, document can be downloaded.
    Click  "Alle gepubliceerde besluiten"
    Click  "Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021"
    Click  "Zoeken in deze documenten..."
    Get Text   //body  *=  792 documenten in 1 besluit
    #search by documentnumber
    Search For  SEARCH_TERM=701061
    ...    SEARCH_RESULTS=701061
    # The search term is the exact match of the besluitdossier. The first result should be the besluitdossier.
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Reload
    Get Text   //h1  *=  Dagstart bespreeklijst Toegangstesten.docx.docx
    Get Text   //body  *=  14 mei 2021
    Get Text   //body  *=  Documentnummer: VWS-WOO-701061
    Get Text   //body  *=  Klik op een pagina om de PDF (249.7 KB) te openen in je browser
    Get Text   //body  *=  14 mei 2021
    Get Text   //body  *=  Word-document, 4 pagina's
    Get Text   //body  *=  PDF (249.7 KB
    Get Text   //body  *=  701061
    Get Text   //body  *=  Overleg
    Get Text   //body  *=  Deels openbaar
    Get Text   //body  *=  5.1.2e Eerbiediging van de persoonlijke levenssfeer
    Get Text   //body  *=  Kom je in dit document gegevens tegen waarvan je denkt dat deze gelakt hadden moeten worden? Of is het document slecht leesbaar? Laat het ons weten.
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
    Get Text   //body  *=  6 mei 2021
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
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
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
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
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
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Click  xpath=//*[@data-e2e-name="download-documents-button"]
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  88
    ${filename}  Get Text  xpath=//*[@data-e2e-name="file-name"]
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  xpath=//*[@data-e2e-name="download-file-link"]
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    28796811
    Remove File  ${besluitdossier_location}

Download large besluitdossier
    [Documentation]  Download a large (>1GB) pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Search For  SEARCH_TERM=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
    ...    SEARCH_RESULTS=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
    Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
    Click  xpath=//*[@data-e2e-name="download-documents-button"]
    Get Text   //body  *=  Download document archief
    Get Text   //body  *=  Het archief is gereed voor download
    Get Text   //body  *=  3767
    ${filename}  Get Text  xpath=//*[@data-e2e-name="file-name"]
    ${besluitdossier_location}     Set Variable    ${OUTPUT DIR}/${filename}
    ${dl_promise}          Promise To Wait For Download    ${besluitdossier_location}
    Click  xpath=//*[@data-e2e-name="download-file-link"]
    Wait For    ${dl_promise}
    File Should Exist  ${besluitdossier_location}
    ${filesize_besluitdossier}  Get File Size  ${besluitdossier_location}
    Should Be Equal As Numbers    ${filesize_besluitdossier}    6238491622
    Remove File  ${besluitdossier_location}

Filter besluitdossiers
    [Documentation]  Filter the existing besluitdossier by daterange
    #The existing besluitdossier has a period of may 2021
    #    [------------Besluitdossier------------]
    #    [------Filter--------------------------]
    #Date-from 29/04/2021
    #Date-to 30/04/2021
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-04-29&dt%5Bto%5D=2021-04-30
    Take Screenshot    fullPage=True
    Get Text  //body   not contains  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    #    [------------Besluitdossier------------]
    #    [--------------------------Filter------]
    #Date-from 01/06/2023
    #Date-to 02/06/2023
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-06-01&dt%5Bto%5D=2021-06-02
    Take Screenshot    fullPage=True
    Get Text  //body   not contains  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    #    [------------Besluitdossier------------]
    #    [-------------------------Filter-------]
    #Date-from 31/05/2021
    #Date-to 02/06/2022
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-05-31&dt%5Bto%5D=2021-06-02
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    #    [------------Besluitdossier------------]
    #    [-------Filter-------------------------]
    #Date-from 30/04/2021
    #Date-to 01/05/2021
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-04-30&dt%5Bto%5D=2021-05-01
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    #    [------------Besluitdossier------------]
    #    [----------------Filter----------------]
    #Date-from 01/05/2021
    #Date-to 31/05/2021
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-05-01&dt%5Bto%5D=2021-05-31
    Take Screenshot    fullPage=True
    Get Text  //body   *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    #    [------------Besluitdossier------------]
    #    -----------------Filter----------------]
    #Date-from -
    #Date-to 31/05/2021
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bto%5D=2021-05-31
    Get Text  //body   *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Take Screenshot    fullPage=True
    #    [------------Besluitdossier------------]
    #    [----------------Filter-----------------
    #Date-from 01/05/2022
    #Date-to -
    Go To  ${base_url}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-05-01
    Get Text  //body   *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
    Take Screenshot    fullPage=True
