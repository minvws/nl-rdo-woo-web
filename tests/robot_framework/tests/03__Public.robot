*** Settings ***
Documentation       Tests that focus on the public pages.
...                 This is named 03 because we want to run this after 02, so we have content to search for.
...                 To run only this suite, run the tag 'public-init'.
Resource            ../resources/Setup.resource
Resource            ../resources/Public.resource
Resource            ../resources/Dossier.resource
Library             DependencyLibrary
Suite Setup         Suite Setup
Test Setup          Test Setup
Test Tags           ci  public  public-init

*** Test Cases ***
Filter Options For Dossiers
  [Documentation]  Tellingen moeten overeenkomen tussen samenvattingsregel en gekozen filteropties
  ...  Depends On Suite  TestDossiers
  # Step by step filter on more dossier types
  ${woo_count}  ${woo_publication_count} =  Select Filter Options - Dossier  woo-decision
  VAR  ${result_count} =  ${woo_count}
  VAR  ${publication_count} =  ${woo_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}
  ${ar_count}  ${ar_publication_count} =  Select Filter Options - Dossier  annual-report
  ${result_count} =  Evaluate  ${result_count} + ${ar_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${ar_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}
  ${covenant_count}  ${covenant_publication_count} =  Select Filter Options - Dossier  covenant
  ${result_count} =  Evaluate  ${result_count} + ${covenant_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${covenant_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}
  ${disposition_count}  ${disposition_publication_count} =  Select Filter Options - Dossier  disposition
  ${result_count} =  Evaluate  ${result_count} + ${disposition_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${disposition_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}
  ${ir_count}  ${ir_publication_count} =  Select Filter Options - Dossier  investigation-report
  ${result_count} =  Evaluate  ${result_count} + ${ir_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${ir_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}
  ${cj_count}  ${cj_publication_count} =  Select Filter Options - Dossier  complaint-judgement
  ${result_count} =  Evaluate  ${result_count} + ${cj_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${cj_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}

Filter Options For Document Types
  ${result_count} =  Select Filter Options - Document Type  document_type=pdf
  Compare Search Result Summary  ${result_count}  IGNORE
  ${em_count} =  Select Filter Options - Document Type  document_type=email
  ${doc_count} =  Select Filter Options - Document Type  document_type=doc
  ${pres_count} =  Select Filter Options - Document Type  document_type=presentation
  ${result_count} =  Evaluate  ${result_count} + ${em_count} + ${doc_count} + ${pres_count}
  Compare Search Result Summary  ${result_count}  IGNORE
  ${em_count} =  Select Filter Options - Document Type  document_type=email  checked=${FALSE}
  ${result_count} =  Evaluate  ${result_count} - ${em_count}
  Compare Search Result Summary  ${result_count}  IGNORE

Searching in dossier should only search in dossier
  Select Filter Options - Dossier  woo-decision  documents=${False}  attachments=${False}  main_document=${FALSE}
  Click First Search Result With Documents
  ${number_of_documents} =  Get Text  //*[@data-e2e-name="dossier-document-count"]
  ${number_of_documents} =  Remove String Using Regexp  ${number_of_documents}  \\D
  ${number_of_attachments} =  Get Element Count  //tr[@data-e2e-name="dossier-attachments-row"]
  VAR  ${decision_document} =  1
  ${docs_in_dossier} =  Evaluate
  ...  ${number_of_documents} + ${number_of_attachments} + ${decision_document}
  Click Search Through Documents In Dossier
  Select Filter Options - Dossier  woo-decision  publications=${FALSE}
  Compare Search Result Summary  ${docs_in_dossier}  1

Sorting on publication date
  [Documentation]  This test is functionally working, but the testdata is all published at the same date...
  Select Filter Options - Dossier  woo-decision  documents=${False}  attachments=${False}  main_document=${FALSE}
  Selecting Results Sorting  newest-first
  Verify Search Results Sort Order  newest-first
  Selecting Results Sorting  oldest-first
  Verify Search Results Sort Order  oldest-first

Filter on dates
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


*** Keywords ***
Suite Setup
  Suite Setup Generic

Test Setup
  Go To Public
  Click On Search Submit

Verify Search Results Sort Order
  [Arguments]  ${sorting_order}
  VAR  @{search_results} =  @{EMPTY}
  ${nr_of_elements} =  Get Element Count  //li[@data-e2e-name="search-result"]//span[@data-e2e-name="publication-date"]
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
  Take Screenshot
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
