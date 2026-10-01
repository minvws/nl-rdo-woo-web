*** Settings ***
Documentation       Tests that focus on the public pages.
...                 This is named 03 because we want to run this after 02, so we have content to search for.
...                 To run only this suite, run the tag 'public-init'.
...                 These tests assert exact counts against the full search index, so they must run
...                 with no other suite concurrently publishing or mutating dossiers.
Resource            ../../resources/Dossier.resource
Resource            ../../resources/Setup.resource
Suite Setup         Suite Setup
Test Setup          Test Setup
Test Teardown       Run Keyword If Test Failed  Take Screenshot
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
  ${dd_count}  ${dd_publication_count} =  Select Filter Options - Dossier  draft-decision
  ${result_count} =  Evaluate  ${result_count} + ${dd_count}
  ${publication_count} =  Evaluate  ${publication_count} + ${dd_publication_count}
  Compare Search Result Summary  ${result_count}  ${publication_count}

Filter Options For Document Types
  ${pdf_count} =  Select Filter Options - Document Type  document_type=pdf
  ${em_count} =  Select Filter Options - Document Type  document_type=email
  ${doc_count} =  Select Filter Options - Document Type  document_type=doc
  ${pres_count} =  Select Filter Options - Document Type  document_type=presentation
  ${result_count} =  Evaluate  ${pdf_count} + ${em_count} + ${doc_count} + ${pres_count}
  Compare Search Result Summary  ${result_count}  IGNORE
  ${em_count} =  Select Filter Options - Document Type  document_type=email  checked=${FALSE}
  ${result_count} =  Evaluate  ${result_count} - ${em_count}
  Compare Search Result Summary  ${result_count}  IGNORE

Searching In Dossier Should Only Search In Dossier
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


*** Keywords ***
Suite Setup
  Suite Setup Generic

Test Setup
  Go To Public
  Click On Search Submit
