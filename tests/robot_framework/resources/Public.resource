*** Settings ***
Documentation       Resource file with generic keywords.
Library             Browser


*** Variables ***
${BASE_URL}     ${EMPTY}


*** Keywords ***
Search For
  [Arguments]
  ...  ${search_term}=
  ...  ${search_results}=
  ...  ${search_results2}=
  ...  ${search_results3}=
  ...  ${not_visible1}=default
  Go To  ${BASE_URL}
  Fill Text  id=search-field  ${search_term}
  Keyboard Key  press  Enter
  Get Text  //*[@id="main-content"]  *=  ${search_results}
  Get Text  //body  *=  ${search_results2}
  Get Text  //body  *=  ${search_results3}
  IF  "${not_visible1}" != "default"
    Get Text  //*[@id="main-content"]/div  not contains  ${not_visible1}
  END
  Reload

Verify Listed Document In Dossier
  [Arguments]  ${document_number}  ${name}  ${date}
  Get Text  //body  *=  ${document_number}
  Get Text  //body  *=  ${name}
  Get Text  //body  *=  ${date}
