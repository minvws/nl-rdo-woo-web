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

arch returns search suggestions
    [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
    Search For  SEARCH_TERM=documanten  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten
    ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    Should Contain  ${suggestSearchElement}   documenten
    Get Text   //body  *=  We konden geen documenten vinden die passen bij uw zoekopdracht "documanten".
    Get Text   //body  *=  Bedoelde u misschien een van de volgende zoektermen: [ documenten ]


Download small besluitdossier
    [Documentation]  Download a small (<30MB) pre-defined besluitdossier, check if the file exists and verify the exact filesize of the download
    Click  "Alle gepubliceerde besluiten"
    Click  "Besluit op uw Wob-verzoek inzake de financiële steun die het kabinet heeft verleend aan KLM"
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
    Click  "Alle gepubliceerde besluiten"
    Click  "Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020"
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
