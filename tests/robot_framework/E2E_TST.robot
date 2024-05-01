*** Settings ***
Documentation       End-to-end tests for the Test environment.
...                 These tests change the database state, so should not be executed on Acceptance/Staging.
Library             DebugLibrary
Resource            resources/Setup.resource
Resource            resources/Public.resource
Suite Setup         Test Suite Setup
Test Tags           e2e_tst  e2e  tst  test


*** Variables ***
${BASE_URL}             https://${TST_USER}:${TST_PASSWORD}@web.test.woo.rdobeheer.nl
${BASE_URL_BALIE}       https://${TST_USER}:${TST_PASSWORD}@balie.test.woo.rdobeheer.nl
${TST_USER}             %{USERNAME_WOO_TEST}
${TST_PASSWORD}         %{PASSWORD_WOO_TEST}
${TST_BALIE_USER}       %{EMAIL_WOO_TEST_BALIE}
${TST_BALIE_PASSWORD}   %{PASSWORD_WOO_TEST_BALIE}
${OTP_CODE}             %{SECRET_WOO_TEST_BALIE}


*** Test Cases ***
Create a new Decision Dossier, filter it and delete it afterwards
  [Documentation]  Login into Balie, create a new decision dossier with some validation.
  ...  After creating succesfully, use the filter function to make sure it can be found. Last step is to delete it.
  Login Balie
  Create New Decision Dossier  upload_files=${FALSE}  env=test
  Filter Decision Dossier
  Delete Concept Decision Dossier

Basic search
  [Documentation]  Do a basic search and check if it returns results
  Search For  search_term=notulen  search_results=notulen
  Get Text  //body  *=  notulen

Basic search and check URL
  [Documentation]  Do a basic search and check if the URL contains the search term
  Search For  search_term=notulen  search_results=notulen
  ${url} =  Get Url
  Should Contain  ${url}  search?q=notulen

Search returns search suggestions
  [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
  Search For  search_term=documanten  search_results=0 documenten in 0 besluiten
  Get Text  //*[@id="js-search-results"]  contains  Bedoelde u misschien een van de volgende zoektermen: documenten
