*** Settings ***
Documentation       Tests for the WooDecision information category.
Resource            ../../resources/Dossier.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/TestData.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  woodecision


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
In A Public Dossier With N Public Files, Retract One Of The Documents
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar.xlsx
  ...  files/woodecision/documenten - 2.zip
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=2
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  ${doc_ids}[1001]
  Retract Document
  Click Breadcrumb Element  3
  Verify Document Retraction  ${doc_ids}[1001]
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er is 1 document ingetrokken.

In A Public Dossier With N Public Files, Retract All Documents Via The Danger Zone
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar.xlsx
  ...  files/woodecision/documenten - 2.zip
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=2
  Search For A Publication  ${DOSSIER_REFERENCE}
  Danger Zone Withdraw All Documents
  Verify Document Retraction  ${doc_ids}[1001]
  Verify Document Retraction  ${doc_ids}[1002]
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er zijn 2 documenten ingetrokken.

Upload A Production Report With N Public Files And A Zip With N-1 Files
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10-1.zip
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report}
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  ${docs}
  Verify Document Upload Remaining  Nog te uploaden: 1 van 10 documenten.
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet  Er moet nog 1 document geüpload worden.

Upload A Production Report With N Public Files And A Zip With N+1 Files
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10+1.zip
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report}
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  ${docs}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  This is a non-published document

Upload A Production Report With N Public Files, M Non-public Files, And A Zip With N + M Files
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  files/woodecision/documenten - 10.zip
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report}
  Verify Document Upload Remaining  Nog te uploaden: 8 van 8 documenten.
  Upload And Process Documents  ${docs}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Check Document Existence On Public  duizendacht
  Check Document Existence On Public  duizendtien

Upload A Production Report With N Public Files, M Already Public Files, And A Zip With N + M Files
  ${prod_report_2}  ${docs_2}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar.xlsx
  ...  files/woodecision/documenten - 2.zip
  ${prod_report_8}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report_2}
  ...  documents=${docs_2}
  ...  number_of_documents=2
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report_8}  ${TRUE}
  Verify Production Report Error  Regel 1: documentnummer ${doc_ids}[1001] bestaat al in een ander dossier
  Verify Production Report Error  Regel 2: documentnummer ${doc_ids}[1002] bestaat al in een ander dossier

In A Public Dossier With N Public And M Non-public Documents, Replace The Production Report With One Where 1 Non-public Document Has Been Made Public
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  files/woodecision/documenten - 8.zip
  ${replacement_report}  ${_}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 9 openbaar 1 niet openbaar.xlsx
  ...  existing_mapping=${doc_ids}
  ${replacement_doc} =  Rename Document File  files/woodecision/1008.pdf  ${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  ${replacement_report}
  ...  1 bestaand document wordt aangepast.
  Verify Document Upload Remaining  Nog te uploaden: 1 van 9 documenten.
  Check If Public Page Has Notification  ${doc_ids}[1008]
  Upload And Process Documents  ${replacement_doc}
  Wait For Elements State  //div[@data-e2e-name="has-changes"]  attached  timeout=30s
  Get Text  //div[@data-e2e-name="has-changes"]  contains  1 document toevoegen
  Get Text  //div[@data-e2e-name="has-changes"]  contains  0 documenten opnieuw publiceren
  Get Text  //div[@data-e2e-name="has-changes"]  contains  0 documenten vervangen
  Click  //button[@data-e2e-name="confirm-document-processing"]
  Click  //button[@data-e2e-name="back-to-uploading"]
  Wait For Elements State  //button[@data-e2e-name="back-to-uploading"]  detached
  Wait For Elements State  //div[@data-e2e-name="upload-busy"]  detached  timeout=30s
  Click Publications
  Get Text  //table[@data-e2e-name="dossiers-table"]//tr[contains(.,'${DOSSIER_REFERENCE}')]  not contains  Incompleet

In A Public Dossier With N Public And M Non-public Documents, Replace The Production Report With One Where 1 Public Document Has Been Made Non-public
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 8 openbaar 2 niet openbaar.xlsx
  ...  files/woodecision/documenten - 8.zip
  ${replacement_report}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 7 openbaar 3 niet openbaar.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=8
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  ${replacement_report}
  ...  1 bestaand document wordt aangepast.
  Open Document In Dossier  ${doc_ids}[1009]
  Verify Document History  Beoordeling aangepast naar niet openbaar
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Openbaar
  Click Public URL
  Verify Notification  besloten dit document niet openbaar te maken.

In A Public Dossier With N Public Files, Replace The Production Report With One Where 1 Public Document Is Suspended
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10.zip
  ${replacement_report}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar 1 opgeschort.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  ${replacement_report}
  ...  1 bestaand document wordt aangepast.
  Open Document In Dossier  ${doc_ids}[1010]
  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Opgeschort
  Click Public URL
  Verify Notification
  ...  Er loopt nog een procedure over dit document met een betrokkene. We kunnen dit document daarom nog niet tonen.
  Verify Document History  Opgeschort
  Go To Admin
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en opgeschort  Er is 1 document opgeschort.

Create A Publication That Becomes Public In The Future
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10.zip
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report}
  Verify Document Upload Remaining  Nog te uploaden: 10 van 10 documenten.
  Upload And Process Documents  ${docs}
  Verify Document Upload Completed
  Click Continue To Publish
  ${timestamp} =  Get Current Date
  ${next_week} =  Add Time To Date  ${timestamp}  7 days
  Fill Publication Date  ${next_week}
  Click Save And Prepare
  ${today_localized} =  Convert Timestamp Format  ${timestamp}  time_format=d MMMM y  locale=nl
  ${next_week_localized} =  Convert Timestamp Format  ${next_week}  time_format=d MMMM y  locale=nl
  Verify Publication Confirmation  ${today_localized}  ${next_week_localized}
  Click  //*[@data-e2e-name="dossier-public-dossier-link"]
  Verify Page Error  404

In A Public Dossier With N Public Files, Replace The Production Report With One Row Missing
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10.zip
  ${replacement_report}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 9 openbaar.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  ${replacement_report}  ${TRUE}
  Verify Production Report Replace  ${doc_ids}[1001] mist in het productierapport

In A Public Dossier With N Public Files, Replace The Production Report With A Copy Where One Document Is Replaced With A New Document
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar.xlsx
  ...  files/woodecision/documenten - 10.zip
  ${replacement_report}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 10 openbaar waarvan 1 verwisseld.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=10
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  ${replacement_report}  ${TRUE}
  Verify Production Report Replace  ${doc_ids}[1001] mist in het productierapport

The Content Of The Published Pdf Should Not Show Up In The Admin Search
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/halieborabotttejetoe/productierapport.xlsx
  ...  files/woodecision/halieborabotttejetoe/3453455.pdf
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=1
  Verify Admin Search Results  halieborabotttejetoe  0

Retract A Document And Then Make It Non-public
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar.xlsx
  ...  files/woodecision/documenten - 2.zip
  ${replacement_report}  ${_}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 niet openbaar.xlsx
  ...  existing_mapping=${doc_ids}
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=2
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  ${doc_ids}[1001]
  Retract Document
  Click Breadcrumb Element  3
  Verify Document Retraction  ${doc_ids}[1001]
  Verify Publication Status  ${DOSSIER_REFERENCE}  Incompleet en ingetrokken  Er is 1 document ingetrokken.
  Search For A Publication  ${DOSSIER_REFERENCE}
  Replace Production Report
  ...  ${replacement_report}
  ...  2 bestaande documenten worden aangepast.
  Click Breadcrumb Element  2
  Verify Publication Action Status  ${DOSSIER_REFERENCE}  ${EMPTY}

Publish A WooDecision With Different Suspended And Withdrawn States
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - mix.xlsx
  ...  files/woodecision/documenten - 10.zip
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=4
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  ${doc_ids}[1002]
  Retract Document
  Click Breadcrumb Element  2  # Element 2 is dossier root
  Click Public URL
  Reload
  Scroll To Element  //*[@data-e2e-name="tabs-documenten-button-1"]
  Get Text  //*[@data-e2e-name="dossier-document-count"]  equals  10 documenten
  Get Text  //*[@data-e2e-name="suspended-withdrawn"]  contains  6 openbaar gemaakt
  Reload  # Extra reload to make sure the retraction has been processed and the tab text updated accordingly
  Verify Dossier Document Count  3  1  1  5

WooDecision Period Configurations Are Displayed Correctly On Public
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar.xlsx
  ...  files/woodecision/documenten - 2.zip
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details  date_from=2021-12-01  date_to=2023-01-31  type=woo-decision
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${prod_report}
  Verify Document Upload Remaining  Nog te uploaden: 2 van 2 document
  Upload And Process Documents  ${docs}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Get Text  //*[@data-e2e-name="dossier-metadata-period"]  contains  December 2021 t/m januari 2023
  Go Back
  Update Period And Verify On Public  2021-12-01  ${EMPTY}  Vanaf december 2021
  Update Period And Verify On Public  ${EMPTY}  2023-01-31  Tot januari 2023
  Update Period And Verify On Public  ${EMPTY}  ${EMPTY}  Alles

Publish A WooDecision With Production Report Without Matter And PublicationContext Column
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report
  ...  files/woodecision/productierapport - 2 openbaar zonder matter en publicatiecontext.xlsx
  ...  ${TRUE}
  Verify Production Report Error
  ...  moet een waarde bevatten voor "Matter" of "Publicatiecontext"

Publish A WooDecision With Production Report With PublicationContext Column
  ${prod_report}  ${docs}  ${doc_ids} =  Randomize Production Report
  ...  files/woodecision/productierapport - 2 openbaar met publicatiecontext.xlsx
  ...  files/woodecision/documenten - 2.zip
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=2
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Open Document In Dossier  ${doc_ids}[1001]
  Get Text  //*[@data-e2e-name="document-nr"]  equals  PUBCON-${doc_ids}[1001]

Upload A Production Report With Both Matter And PublicationContext Columns Should Fail
  Click Publications
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report
  ...  files/woodecision/productierapport - 2 openbaar met matter en publicatiecontext.xlsx
  ...  ${TRUE}
  Verify Production Report Error
  ...  Productierapport met een kolom "Publicatiecontext" kan geen kolom voor "Matter" bevatten

Verify PDF Preview Thumbnail
  [Documentation]  Depends on the first testcase, since it needs a fully ingested dossier.
  ${prod_report}  ${docs}  ${_} =  Randomize Production Report
  ...  files/woodecision/productierapport - pdf.xlsx
  ...  files/woodecision/10999.pdf
  Click Publications
  Publish Test WooDecision
  ...  production_report=${prod_report}
  ...  documents=${docs}
  ...  number_of_documents=1
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Public URL
  Click  (//*[@data-e2e-name="tabs-documenten-content-1"]//tbody//a)[1]
  Verify Document Preview Thumbnails  4
  Generic Download Click  //a[@data-e2e-name="download-file-link"]


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
  Close Browser

Verify Document Retraction
  [Arguments]  ${document_id}
  Open Document In Dossier  ${document_id}
  Wait Until Keyword Succeeds
  ...  2 min
  ...  5 sec
  ...  Verify Document Details
  ...  download_type=niet van toepassing
  ...  publication_status=Ingetrokken
  Click Public URL
  Verify Notification  De reden dat het document is ingetrokken:
  Verify Document History  Ingetrokken met reden
  Go Back
  Go Back

Verify Publication Status
  [Arguments]  ${dossier_reference}  ${expected_status}  ${expected_document_notification}
  Click Publications
  Click Publication By Value  ${dossier_reference}
  Get Text  //*[@data-e2e-name="has-document-notifications"]  contains  Documenten vereisen aandacht
  Get Text  //*[@data-e2e-name="document-notifications"]  contains  ${expected_document_notification}
  Verify Publication Action Status  ${dossier_reference}  ${expected_status}

Replace Production Report
  [Arguments]  ${replacement_production_report}  ${expected_replacement_message}
  Click Documents Edit
  Click Replace Report
  Upload Production Report  ${replacement_production_report}  ${TRUE}
  Verify Production Report Replace  Productierapport geüpload en gecontroleerd
  Verify Production Report Replace  ${expected_replacement_message}
  Click Confirm Production Report Replacement
  Verify Production Report Replace  Het productierapport is succesvol vervangen.
  Click Continue To Documents

Check If Public Page Has Notification
  [Arguments]  ${document_id}
  ${location} =  Get Url
  Open Document In Dossier  ${document_id}
  Click Public URL
  Get Text
  ...  //*[@data-e2e-name="main-content"]
  ...  contains
  ...  Dit bestand zal spoedig aangeleverd worden: probeert u later nog eens.
  Go To  ${location}

Update Period And Verify On Public
  [Arguments]  ${date_from}  ${date_to}  ${expected_period}
  Click Edit Details
  Select Options By  id=details_date_from  value  ${date_from}
  Select Options By  id=details_date_to  value  ${date_to}
  Click  "Bewerken en opslaan"
  Click Public URL
  Reload
  Get Text  //*[@data-e2e-name="dossier-metadata-period"]  contains  ${expected_period}
  Go Back
