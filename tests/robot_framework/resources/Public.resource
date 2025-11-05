*** Comments ***
# robocop: off=too-many-arguments,too-long-keyword


*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             String
Library             Browser
Library             DateTimeTZ
Library             DebugLibrary
Library             DependencyLibrary
Resource            Generic.resource


*** Variables ***
${URL_PUBLIC}   ${EMPTY}


*** Keywords ***
Go To Public
  Go To  ${URL_PUBLIC}

Search On Public For
  [Documentation]  Provide the expected search results. '0 resultaten' can be used to expect no results.
  [Arguments]  ${search_term}  ${search_results}
  Go To Public
  Initiate Search  ${search_term}
  Get Text  //div[@id="js-search-results"]  contains  ${search_results}

Click On Search Submit
  Click  //form[@role="search"]//button[@type="submit"]

Initiate Search
  [Arguments]  ${search_term}
  Fill Text  id=search-field  ${search_term}
  Click On Search Submit

Verify Search Result Count
  [Arguments]  ${result_string}
  Get Text  //*[@id="js-number-of-search-results"]  contains  ${result_string}

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

Select Filter Options - Dossier
  [Arguments]
  ...  ${dossier_type}
  ...  ${checked}=${TRUE}
  ...  ${publications}=${TRUE}
  ...  ${documents}=${TRUE}
  ...  ${attachments}=${TRUE}
  ...  ${main_document}=${TRUE}
  IF  '${dossier_type}' == 'woo-decision'
    VAR  ${id} =  dossier
  ELSE IF  '${dossier_type}' == 'annual-report'
    VAR  ${id} =  annual_report
  ELSE IF  '${dossier_type}' == 'covenant'
    VAR  ${id} =  covenant
  ELSE IF  '${dossier_type}' == 'disposition'
    VAR  ${id} =  disposition
  ELSE IF  '${dossier_type}' == 'investigation-report'
    VAR  ${id} =  investigation_report
  ELSE IF  '${dossier_type}' == 'complaint-judgement'
    VAR  ${id} =  complaint_judgement
  END
  IF  ${checked}
    Check Checkbox  id=input_${id}
    IF  not ${publications}
      Wait For Elements State  //input[@data-e2e-name="input_${id}.publication"]  visible
      Uncheck Checkbox  //input[@data-e2e-name="input_${id}.publication"]
    END
    IF  not ${main_document}
      IF  '${dossier_type}' == 'woo-decision'
        Wait For Elements State  //input[@data-e2e-name="input_${id}.woo_decision_main_document"]  visible
        Uncheck Checkbox  //input[@data-e2e-name="input_${id}.woo_decision_main_document"]
      ELSE
        Wait For Elements State  //input[@data-e2e-name="input_${id}.${id}_main_document"]  visible
        Uncheck Checkbox  //input[@data-e2e-name="input_${id}.${id}_main_document"]
      END
    END
    IF  '${dossier_type}' == 'woo-decision' and not ${documents}
      Wait For Elements State  //input[@data-e2e-name="input_${id}.document"]  visible
      Uncheck Checkbox  //input[@data-e2e-name="input_${id}.document"]
    END
    IF  not ${attachments} and '${dossier_type}' != 'complaint-judgement'
      Wait For Elements State  //input[@data-e2e-name="input_${id}.attachment"]  visible
      Uncheck Checkbox  //input[@data-e2e-name="input_${id}.attachment"]
    END
  ELSE
    Uncheck Checkbox  id=input_${id}
  END
  Sleep  1s  # Wait for the subpage to be reloaded
  ${total_count} =  Get Text  //span[@data-e2e-name="${id}_count"]
  ${total_count} =  Remove String Using Regexp  ${total_count}  [()]
  ${publication_count} =  Get Text  //span[@data-e2e-name="${id}.publication_count"]
  ${publication_count} =  Remove String Using Regexp  ${publication_count}  [()]
  Sleep  2s  # Wait for page update
  RETURN  ${total_count}  ${publication_count}  #  ${document_count}  ${attachment_count}

Get Search Result Count
  ${text} =  Get Text  //span[@data-e2e-name="result-count"]
  ${results} =  Remove String Using Regexp  ${text}  \\D
  RETURN  ${results}

Get Search Dossier Count
  ${text} =  Get Text  //span[@data-e2e-name="dossier-count"]
  ${results} =  Remove String Using Regexp  ${text}  \\D
  RETURN  ${results}

Compare Search Result Summary
  [Arguments]  ${expected_results}  ${expected_publications}
  ${results} =  Get Search Result Count
  Should Be Equal As Numbers  ${results}  ${expected_results}
  IF  '${expected_publications}' != 'IGNORE'
    ${publications} =  Get Search Dossier Count
    Should Be Equal As Numbers  ${publications}  ${expected_publications}
  END

Select Filter Options - Document Type
  [Arguments]  ${document_type}  ${checked}=${TRUE}
  ${filter_count} =  Get Element Count  id=input_${document_type}
  IF  ${filter_count}==1
    IF  ${checked}
      Check Checkbox  id=input_${document_type}
    ELSE
      Uncheck Checkbox  id=input_${document_type}
    END
    ${count} =  Get Text  //span[@data-e2e-name="${document_type}_count"]
    ${count} =  Remove String Using Regexp  ${count}  [()]
    Sleep  2s
    RETURN  ${count}
  ELSE
    RETURN  0
  END

Click First Search Result With Documents
  ${elements} =  Get Elements  css=li[data-e2e-name="search-result"]
  FOR  ${element}  IN  @{elements}
    ${text} =  Get Text  ${element} >> ul li[data-e2e-name="nr-of-documents"]
    VAR  ${text} =  ${text.strip()}
    IF  '${text}' != '0 documenten'
      Click  ${element} >> h3 a
      BREAK
    END
  END

Selecting Results Sorting
  [Arguments]  ${sorting_option}
  Click  //details[@data-e2e-name="sorting-selector"]
  IF  '${sorting_option}' == 'newest-first'
    VAR  ${sorting_option_text} =  Publicatiedatum (Nieuwste bovenaan)
  ELSE IF  '${sorting_option}' == 'oldest-first'
    VAR  ${sorting_option_text} =  Publicatiedatum (Oudste bovenaan)
  ELSE IF  '${sorting_option}' == 'decision=date'
    VAR  ${sorting_option_text} =  Besluitdatum
  END
  Click  //details[@data-e2e-name="sorting-selector"]//li[contains(.,'${sorting_option_text}')]
  Get Element States  //details[@data-e2e-name="sorting-selector"]/div  contains  hidden

Click Bekijk Per Bestuursorgaan
  Click With Reload Retry  //ul[@data-e2e-name="main-nav"]//a[contains(.,'Bekijk per bestuursorgaan')]  sleep=1s

Select Filter Options - Date
  [Arguments]  ${date_from}  ${date_to}
  Apply Date Filters Through URL  ${date_from}  ${date_to}
  ${date_from} =  Convert Date  date=${date_from}  date_format=%d-%m-%Y
  ${date_to} =  Convert Date  date=${date_to}  date_format=%d-%m-%Y
  ${from_text} =  Convert Timestamp Format  timestamp=${date_from}  locale=nl  time_format=d MMMM Y
  ${to_text} =  Convert Timestamp Format  timestamp=${date_to}  locale=nl  time_format=d MMMM Y
  Get Element Count
  ...  //li[@data-e2e-name="facet-pill"]//span[contains(.,'${from_text}')]
  ...  equals
  ...  1
  ...  message=A facet pill should contain '${from_text}'
  Get Element Count
  ...  //li[@data-e2e-name="facet-pill"]//span[contains(.,'${to_text}')]
  ...  equals
  ...  1
  ...  message=A facet pill should contain '${to_text}'

Apply Date Filters Through URL
  [Arguments]  ${date_from}  ${date_to}
  ${date_from} =  Convert Date  date=${date_from}  date_format=%d-%m-%Y
  ${date_to} =  Convert Date  date=${date_to}  date_format=%d-%m-%Y
  ${from_url} =  Convert Date  date=${date_from}  result_format=%Y-%m-%d
  ${to_url} =  Convert Date  date=${date_to}  result_format=%Y-%m-%d
  ${current_url} =  Get Url
  Go To  url=${current_url}&dt%5Bfrom%5D=${from_url}&dt%5Bto%5D=${to_url}

Navigate To Covid19 Theme Page
  ${hamburger_visible} =  Get Element States  //div[@data-e2e-name="hamburger"]  then  bool(value & visible)
  IF  ${hamburger_visible}  Click  //div[@data-e2e-name="hamburger"]
  Click  //a[@data-e2e-name="nav-Thema COVID-19"]

Verify Document Preview Thumbnails
  [Documentation]  Should contain three items and the alt text of the images should say Pagina #
  [Arguments]  ${pages_expected}
  Get Element Count
  ...  //ol[@data-e2e-name="preview-list"]/li
  ...  assertion_operator=>=
  ...  assertion_expected=${pages_expected}
  Get Attribute  (//ol[@data-e2e-name="preview-list"]/li)[1]//img  alt  contains  Pagina 1
  ${url} =  Get Attribute  (//ol[@data-e2e-name="preview-list"]/li)[1]//a  href
  Generic Download URL  ${url}

Click Clear All Filters
  Click  //*[@data-e2e-name="clear-all-filters"]

Click Start A New Search
  Click  //*[@data-e2e-name="start-new-search"]

Click Public Breadcrumb Element
  [Arguments]  ${element_nr}
  Click  //div[@class="woo-container"]/nav/ol/li[${element_nr}]
