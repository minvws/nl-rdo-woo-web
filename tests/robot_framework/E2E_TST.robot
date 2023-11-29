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
#${base_url}               https://${tst_user}:${tst_password}@web.test.woo.rdobeheer.nl
${base_url}   https://${tst_user}:${tst_password}@web.test.woo.rdobeheer.nl
${base_url_balie}         https://${tst_user}:${tst_password}@balie.test.woo.rdobeheer.nl
${tst_user}               %{USERNAME_WOO_TEST}
${tst_password}           %{PASSWORD_WOO_TEST}
${tst_balie_user}         %{EMAIL_WOO_TEST_BALIE}
${tst_balie_password}     %{PASSWORD_WOO_TEST_BALIE}
${otp_code}               %{SECRET_WOO_TEST_BALIE}



*** Test Cases ***
# Not possible to succesfully create a besluitdossier, because several documents already exists in the databse
Login Balie and attempt to create a new besluitdossier
    [Documentation]  Login to Balie and create a besluitdossier with the status 'concept' and delete it afterwards.
    Login Balie
    Take Screenshot    fullPage=True
    Create nieuw besluit(dossier)

Search for notulen
    [Documentation]  Do a basic search and check if it returns results
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=51 documenten in
    Get Text   //body  *=  notulen

Check searchurl
    [Documentation]  Do a basic search and check if the URL contains the search term
    Search For  SEARCH_TERM=notulen  
    ...    SEARCH_RESULTS=51 documenten in
    ${url}  Get Url
    Should Contain  ${url}   search?q=notulen
    Get Text   //body  *=  notulen

Check documanten should suggest documenten
    [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
    Search For  SEARCH_TERM=documanten  
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten
    ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    Should Contain  ${suggestSearchElement}   documenten
    Get Text   //body  *=  We konden geen documenten vinden die passen bij uw zoekopdracht "documanten".
    Get Text   //body  *=  Bedoelde u misschien een van de volgende zoektermen: [ documenten ]

