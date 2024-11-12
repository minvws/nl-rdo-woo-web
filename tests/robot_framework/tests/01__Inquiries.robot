*** Settings ***
Documentation       Tests that focus on using the inquiry system
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Inquiry.resource
Library             Collections
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           inquiries  ci  public-init


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
Preview inquiry access
  [Documentation]  Verify preview access to a dossier using inquiry page
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport4.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten4.zip
  ...  number_of_documents=3
  ...  publication_status=Gepland
  Wait For Queue To Empty
  # Verify the document can be found through the inquiry
  Click Inquiries
  Open Inquiry  2021-01
  Click Search Through Documents In Inquiry
  Verify Search Result Count  3 resultaten in 1 publicatie
  Initiate Search  huppeldepup.docx
  Verify Search Result Count  1 resultaat in 1 publicatie
  Click  "huppeldepup.docx"
  ${document_url} =  Get URL
  # Verify the document can't be found normally
  New Context  locale=nl-NL
  New Page  ${BASE_URL}
  Search On Public For  huppeldepup.docx  0 resultaten
  # Verify the document page can't be accessed normally
  Go To  ${document_url}
  Verify Symfony Error  not found

Link inquiries using production report
  [Documentation]  Create a WooDecision that is part of multiple inquiries using productionreport upload
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport1.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten1.zip
  ...  number_of_documents=9
  Click Inquiries
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2024-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=2  nr_of_partially_published_docs=3  nr_of_unpublished_docs=0
  VAR  @{document_ids} =  3001  3002  3003  3004  3009
  Verify Inquiry Documents  ${document_ids}
  Go Back
  Open Inquiry  2024-02
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2  nr_of_unpublished_docs=1
  VAR  @{document_ids} =  3007  3008  3009
  Verify Inquiry Documents  ${document_ids}
  Go Back
  Open Inquiry  2024-03
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2
  VAR  @{document_ids} =  3003  3004  3007
  Verify Inquiry Documents  ${document_ids}

Manually link inquiry to decision
  [Documentation]  Create a WooDecision without inquiries and manually link the decision
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport2.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten2.zip
  ...  number_of_documents=3
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Decision Linking
  Link Inquiry To Decision  2023-01  ${DOSSIER_REFERENCE}
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2023-01
  Verify Inquiry Dossiers  ${dossier_ids}

Manually link inquiry to documents
  [Documentation]  Create a WooDecision without inquiries and manually link the documents
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport3.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten3.zip
  ...  number_of_documents=3
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Document Linking
  Link Inquiry To Documents  tests/robot_framework/files/inquiries/linking3.xlsx
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Open Inquiry  2022-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=1  nr_of_partially_published_docs=2
  VAR  @{document_ids} =  3201  3202  3203
  Verify Inquiry Documents  ${document_ids}

Production report inquiry does not unlink
  [Documentation]  Unlinking using a production report should not be possible
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport5.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten5.zip
  ...  number_of_documents=2
  Click Publications
  Click Publication By Value  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/inquiries/productierapport5-unlinked.xlsx  ${TRUE}
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  2 bestaande documenten worden aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  Click Breadcrumb Element  1
  Get Text  //*[@data-e2e-name="linked-inquiries"]  contains  2021-02
  Click Inquiries
  Verify Inquiry Document Count  2021-02  2

Manual links are not overwritten when reuploading production report
  [Documentation]  Reuploading the original production report after manually linking documents should not remove the manual links
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport6.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten6.zip
  ...  number_of_documents=2
  Click Inquiries
  Click Manual Inquiry Linking
  Click Manual Woo Document Linking
  Link Inquiry To Documents  tests/robot_framework/files/inquiries/linking6.xlsx
  Open Inquiry  2020-01
  VAR  @{document_ids} =  3501  3502
  Verify Inquiry Documents  ${document_ids}
  Go To Admin
  Search For A Publication  ${DOSSIER_REFERENCE}
  Click Documents Edit
  Click Replace Report
  Upload Inventory  tests/robot_framework/files/inquiries/productierapport6.xlsx  ${TRUE}
  Verify Inventory Replace  Productierapport geüpload en gecontroleerd
  Verify Inventory Replace  2 bestaande documenten worden aangepast.
  Click Confirm Inventory Replacement
  Verify Inventory Replace  De inventaris is succesvol vervangen.
  Click Continue To Documents
  # check if the manual link is been override by the inventory again
  Open Document In Dossier  3501
  Get Text  //*[@data-e2e-name="inquiries"]  contains  2020-01
  Click Breadcrumb Element  2
  Open Document In Dossier  3502
  Get Text  //*[@data-e2e-name="inquiries"]  contains  2020-01

Inquiry with multiple dossiers
  [Documentation]  Create an inquiry with multiple dossiers
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport7a.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten7a.zip
  ...  number_of_documents=9
  VAR  @{dossier_ids} =  ${DOSSIER_REFERENCE}
  Publish Test Dossier
  ...  inventory=tests/robot_framework/files/inquiries/productierapport7b.xlsx
  ...  documents=tests/robot_framework/files/inquiries/documenten7b.zip
  ...  number_of_documents=3
  Append To List  ${dossier_ids}  ${DOSSIER_REFERENCE}
  Click Inquiries
  Open Inquiry  2019-01
  Verify Inquiry Dossiers  ${dossier_ids}
  Verify Inquiry Info  nr_of_published_docs=3  nr_of_partially_published_docs=4  nr_of_unpublished_docs=1
  VAR  @{document_ids} =  3601  3602  3603  3604  3609  3611  3613
  Verify Inquiry Documents  ${document_ids}


*** Keywords ***
Suite Setup
  Suite Setup - CI
  Login Admin
  Select Organisation

Suite Teardown
  Go To Admin
  Logout Admin
