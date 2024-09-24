*** Settings ***
Documentation       Resource file with generic keywords.
Library             Browser
Library             DebugLibrary


*** Variables ***
${BASE_URL}     ${EMPTY}


*** Keywords ***
Go To Public
  Go To  ${BASE_URL}

Search On Public For
  [Arguments]  ${search_term}  ${search_results}
  Go To Public
  Initiate Search  ${search_term}
  Get Text  //*[@id="main-content"]  *=  ${search_results}

Initiate Search
  [Arguments]  ${search_term}
  Fill Text  id=search-field  ${search_term}
  Keyboard Key  press  Enter

Verify Search Result Count
  [Arguments]  ${result_string}
  Get Text  //*[@id="js-number-of-search-results"]  contains  ${result_string}

Verify Listed Document In Dossier
  [Arguments]  ${document_number}  ${name}  ${date}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${document_number}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${name}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${date}

Categories Contain Items  [Arguments]  ${category}  ${item}  ${count}
  IF  $category == "Onderwerp"
    Get Text  //*[@data-e2e-name="categories-Onderwerp"]  contains  ${item}
    Get Text  //*[@data-e2e-name="categories-Onderwerp"]  contains  ${count}
  END
  IF  $category == "Type bronbestand"
    Get Text  //*[@data-e2e-name="categories-Type bronbestand"]  contains  ${item}
    Get Text  //*[@data-e2e-name="categories-Type bronbestand"]  contains  ${count}
  END
  IF  $category == "Soort besluit"
    Get Text  //*[@data-e2e-name="categories-Soort besluit"]  contains  ${item}
    Get Text  //*[@data-e2e-name="categories-Soort besluit"]  contains  ${count}
  END
  IF  $category == "Uitzonderingsgrond"
    Get Text  //*[@data-e2e-name="categories-Uitzonderingsgrond"]  contains  ${item}
    Get Text  //*[@data-e2e-name="categories-Uitzonderingsgrond"]  contains  ${count}
  END

Check Document Existence On Public
  [Documentation]  The document with the given ID should NOT be visible on public
  [Arguments]  ${document_id}
  Go To Public
  Search On Public For  ${document_id}  0 resultaten

Verify Notification
  [Arguments]  ${notification_message}
  Get Text  //*[@data-e2e-name="notifications"]  contains  ${notification_message}

Verify Document History
  [Arguments]  ${history}
  Get Text  //*[@data-e2e-name="document-history"]  contains  ${history}

Click Search Through Documents In Inquiry
  Click  //a[@data-e2e-name="search-through-documents"]

Verify Symfony Error
  [Arguments]  ${error_message}
  Get Text  //div[@class="exception-summary "]  contains  ${error_message}

Click Dossier In Inquiry
  [Arguments]  ${dossier_text}
  Click  //*[@data-e2e-name="inquiry-dossiers"]//a[contains(., ${dossier_text})]
