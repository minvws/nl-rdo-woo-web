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
${base_url}   https://staging:AdelaarNiveauGeleverdeVoorgoed@web.acc.woo.rdobeheer.nl

*** Test Cases ***
Search for notulen
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=50 documenten in 3 besluiten

Check searchurl
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=50 documenten in 3 besluiten
    ${url}  Get Url
    Should Contain  ${url}   search?q=notulen

Check documanten should suggest documenten
    Search For  SEARCH_TERM=documanten  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten
    ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    Should Contain  ${suggestSearchElement}   documenten