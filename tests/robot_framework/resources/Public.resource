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
  [Arguments]
  ...  ${search_term}=
  ...  ${search_results}=
  Go To Public
  Fill Text  id=search-field  ${search_term}
  Keyboard Key  press  Enter
  Get Text  //*[@id="main-content"]  *=  ${search_results}

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
  Search For  ${document_id}  0 documenten
