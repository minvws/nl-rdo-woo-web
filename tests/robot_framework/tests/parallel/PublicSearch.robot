*** Settings ***
Documentation       Tests that focus on the public search page's point-in-time behaviour (sort
...                 order, date-range correctness, filter-pill counts, zero-results).
...                 Depends on 02__TestDossiers having run first to have content to search for.
Resource            ../../resources/Dossier.resource
Resource            ../../resources/Setup.resource
Suite Setup         Suite Setup
Test Setup          Test Setup
Test Teardown       Run Keyword If Test Failed  Take Screenshot
Test Tags           ci  public  public-init


*** Test Cases ***
Sorting On Publication Date
  [Documentation]  This test is functionally working, but the testdata is all published at the same date...
  Select Filter Options - Dossier  woo-decision  documents=${False}  attachments=${False}  main_document=${FALSE}
  Selecting Results Sorting  newest-first
  Verify Search Results Sort Order  newest-first
  Selecting Results Sorting  oldest-first
  Verify Search Results Sort Order  oldest-first

Filter On Dates
  Select Filter Options - Dossier  woo-decision  publications=${FALSE}  documents=${TRUE}  attachments=${TRUE}
  ${today} =  Convert Date  date=${CURRENT_DATE}  result_format=%d-%m-%Y
  ${yesterday} =  Subtract Time From Date  date=${CURRENT_DATE}  time=1 day  result_format=%d-%m-%Y
  ${tomorrow} =  Add Time To Date  date=${CURRENT_DATE}  time=1 day  result_format=%d-%m-%Y
  Select Filter Options - Date  date_from=01-01-2001  date_to=${yesterday}
  Verify Search Results Date Range  date_from=01-01-2001  date_to=${yesterday}
  Select Filter Options - Date  date_from=${yesterday}  date_to=${today}
  Verify Search Results Date Range  date_from=${yesterday}  date_to=${today}
  Select Filter Options - Date  date_from=${tomorrow}  date_to=01-01-2030
  Verify Search Results Date Range  date_from=${tomorrow}  date_to=01-01-2030

Clear All Filters
  Select Filter Options - Dossier  woo-decision
  Select Filter Options - Document Type  doc
  Filter Pill Count Should Be  5
  Click Clear All Filters
  Filter Pill Count Should Be  0

Start New Search Link When No Search Results
  Search On Public For  36b832f7-045e-4b8f-b1eb-13e07381cc67  0
  Compare Search Result Summary  0  0
  Click Start A New Search
  Search Results Should Not Be Zero


*** Keywords ***
Suite Setup
  Suite Setup Generic

Test Setup
  Go To Public
  Click On Search Submit

Verify Search Results Sort Order
  [Arguments]  ${sorting_order}
  VAR  @{search_results} =  @{EMPTY}
  ${nr_of_elements} =  Get Element Count
  ...  //li[@data-e2e-name="search-result"]//span[@data-e2e-name="publication-date"]
  IF  ${nr_of_elements} > 0
    @{result_elements} =  Get Elements  //li[@data-e2e-name="search-result"]//span[@data-e2e-name="publication-date"]
    FOR  ${element}  IN  @{result_elements}
      ${text} =  Get Text  ${element}
      ${date} =  Convert Dutch To English Date  ${text}
      ${epoch} =  Convert Date  date=${date}  date_format=%d-%m-%Y  result_format=epoch
      Append To List  ${search_results}  ${epoch}
    END
  END
  IF  '${sorting_order}' == 'newest-first'
    ${sorted} =  Evaluate  sorted(${search_results}, reverse=True)
  ELSE IF  '${sorting_order}' == 'oldest-first'
    ${sorted} =  Evaluate  sorted(${search_results}, reverse=False)
  END
  Lists Should Be Equal  ${search_results}  ${sorted}

Verify Search Results Date Range
  [Arguments]  ${date_from}  ${date_to}
  ${nr_of_elements} =  Get Element Count  //li/time[@data-e2e-name="document-date"]
  IF  ${nr_of_elements} > 0
    @{result_elements} =  Get Elements  //li/time[@data-e2e-name="document-date"]
    FOR  ${element}  IN  @{result_elements}
      ${text} =  Get Text  ${element}
      ${date} =  Convert Dutch To English Date  ${text}
      ${date_epoch} =  Convert Date  date=${date}  date_format=%d-%m-%Y  result_format=epoch
      ${date_from_epoch} =  Convert Date  date=${date_from}  date_format=%d-%m-%Y  result_format=epoch
      ${date_to_epoch} =  Convert Date  date=${date_to}  date_format=%d-%m-%Y  result_format=epoch
      Should Be True
      ...  ${date_epoch} >= ${date_from_epoch} and ${date_epoch} <= ${date_to_epoch}
      ...  msg=Date ${date} is not within the range of ${date_from} and ${date_to}
    END
  END

Filter Pill Count Should Be
  [Arguments]  ${count}
  Get Element Count  //*[@data-e2e-name="facet-pill"]  should be  ${count}

Search Results Should Not Be Zero
  ${results} =  Get Search Result Count
  ${dossier} =  Get Search Dossier Count
  Should Not Be Equal As Numbers  ${results}  0
  Should Not Be Equal As Numbers  ${dossier}  0
