*** Settings ***
Library    Browser
Library    DebugLibrary
Library    OperatingSystem
Library    OTP
Library    Process
Library    String
Resource    keywords.resource

Force Tags      E2E_TST
Test Setup      Open Browser and BaseUrl

* Variables *
${base_url}               https://${tst_user}:${tst_password}@web.test.woo.rdobeheer.nl
${base_url_balie}         https://${tst_user}:${tst_password}@balie.test.woo.rdobeheer.nl
${tst_user}               %{USERNAME_WOO_TEST}
${tst_password}           %{PASSWORD_WOO_TEST}
${tst_balie_user}         %{EMAIL_WOO_TEST_BALIE}
${tst_balie_password}     %{PASSWORD_WOO_TEST_BALIE}
${otp_code}               %{SECRET_WOO_TEST_BALIE}



*** Test Cases ***
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

Create a new besluitdossier, filter it and delete it afterwards
    [Documentation]  Login into Balie, create a new besluitdossier with some validation, upload documents. After creating succesfully, use the filter function to make sure it can be found. Last step is to delete it.
    Login Balie
    Create nieuw besluit(dossier)
    Filter besluitdossier
    Delete besluitdossier

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

Search returns search suggestions
    [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
    Search For  SEARCH_TERM=documanten
    ...    SEARCH_RESULTS=0 documenten in 0 besluiten
    ${suggestSearchElement}  Get Element  //a[normalize-space()='documenten']
    Should Contain  ${suggestSearchElement}   documenten
    Get Text   //body  *=  We konden geen documenten vinden die passen bij uw zoekopdracht "documanten".
    Get Text   //body  *=  Bedoelde u misschien: documenten

