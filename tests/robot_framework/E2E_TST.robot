*** Settings ***
Documentation       End-to-end tests for the Test environment.
...                 These tests change the database state, so should not be executed on Acceptance/Staging.
Library             DebugLibrary
Resource            resources/Setup.resource
Resource            resources/Public.resource
Resource            resources/Dossier.resource
Suite Setup         Suite Setup - Test
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
  Login Admin
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/woodecision/besluitbrief.pdf
  Upload Inventory  tests/Fixtures/000-inventory-001.xlsx
  Verify Inventory Error  documentnummer 5034 bestaat al in een ander dossier
  Verify Inventory Error  documentnummer 5036 bestaat al in een ander dossier
  Verify Draft Dossier

Filter Decision Dossier
  [Documentation]  Check the filter functionality in the decision dossier page in the Balie
  Go To Admin
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Filter Op Bestuursorgaan En Status  status=Concept
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Status: Concept
  Filter Op Bestuursorgaan En Status  status=Concept
  Filter Op Bestuursorgaan En Status  status=Openbaar
  Get Text  //body  not contains  Robot ${CURRENT_TIME}

Delete Concept Decision Dossier
  Search For A Publication  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Besluit verwijderen
  Click  xpath=//*[@data-e2e-name="delete-dossier-link"]
  Click  xpath=//*[@id="delete_form_submit"]
  Get Text  //body  *=  Het dossier wordt verwijderd, het kan even duren voor dit verwerkt is.
  Sleep  30s
  Click  "Naar overzicht besluitdossiers"

Basic search
  [Documentation]  Do a basic search and check if it returns results
  Search On Public For  search_term=notulen  search_results=notulen
  Get Text  //body  *=  notulen

Basic search and check URL
  [Documentation]  Do a basic search and check if the URL contains the search term
  Search On Public For  search_term=notulen  search_results=notulen
  ${url} =  Get Url
  Should Contain  ${url}  search?q=notulen

Search returns search suggestions
  [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
  Search On Public For  search_term=documanten  search_results=0 documenten in 0 besluiten
  Get Text  //*[@id="js-search-results"]  contains  Bedoelde u misschien een van de volgende zoektermen: documenten
