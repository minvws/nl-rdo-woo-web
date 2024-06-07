*** Settings ***
Documentation       Tests that run on a local stack, modifying it's state.
Resource            resources/Setup.resource
Resource            resources/Generic.resource
Resource            resources/Public.resource
Resource            resources/Admin.resource
Resource            resources/Dossier.resource
Resource            resources/Document.resource
Resource            resources/Inquiry.resource
Suite Setup         Suite Setup - CI
Test Tags           ci


*** Variables ***
${BASE_URL}             localhost:8000
${BASE_URL_BALIE}       localhost:8000/balie/dossiers
${TST_BALIE_USER}       email@example.org
${TST_BALIE_PASSWORD}   IkLoopNooitVastVandaag


*** Test Cases ***
Create decision dossier
  [Documentation]  Create a decision dossier, as a starting point for all other test cases
  Login Admin
  Create WooDecision Dossier - CI
  Verify Published Decision Dossier

Login Admin and filter decision dossier
  [Documentation]  Check the filter functionality in the decision dossier page in the Balie
  Login Admin
  Filter Op Bestuursorgaan En Status  Ministerie van Algemene Zaken
  Get Text  //body  *=  Bestuursorgaan: Ministerie van Algemene Zaken
  Get Text  //body  *=  Er zijn geen publicaties gevonden die aan de filters voldoen.
  Filter Op Bestuursorgaan En Status  Ministerie van Volksgezondheid, Welzijn en Sport
  Get Text  //body  *=  Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Filter Op Bestuursorgaan En Status  Ministerie van Volksgezondheid, Welzijn en Sport  Concept
  Get Text  //body  *=  Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
  Get Text  //body  *=  Status: Concept
  Get Text  //body  *=  Er zijn geen publicaties gevonden die aan de filters voldoen.
  Filter Op Bestuursorgaan En Status  Ministerie van Volksgezondheid, Welzijn en Sport  Openbaar
  Get Text  //body  *=  Bestuursorgaan: Ministerie van Volksgezondheid, Welzijn en Sport
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Status: Openbaar
  Logout Admin

Decision Dossier overview page
  [Documentation]  Locate a existing decision dossier, check if the predefined metadata are available, the numbers match and expected documents are shown
  Search For  search_term=Robot ${CURRENT_TIME}
  ...  search_results=Robot ${CURRENT_TIME}
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
  Verify Dossier Metadata
  ...  dossier_status=Openbaar
  ...  responsible=Ministerie van Volksgezondheid, Welzijn en Sport
  ...  period=December 2021 - januari 2023
  ...  decision_date=${CURRENT_DATE_FORMAT2}
  ...  dossier_type=Woo-verzoek
  ...  publication_size=19 documenten, 19 pagina's
  Verify Listed Document In Dossier  5062  case-2-email-with-more-emails-in-thread-4.pdf  9 oktober 2020
  Verify Listed Document In Dossier  5036  case-2-email-with-more-emails-in-thread-1.pdf  11 augustus 2020

Document overview page (document is made public)
  [Documentation]  Locate a existing document, check if the predefined metadata are available, document can be downloaded.
  Search For  search_term=case-5-mail-with-multi-attachment-no-thread.pdf
  ...  search_results=case-5-mail-with-multi-attachment-no-thread.pdf
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  case-5-mail-with-multi-attachment-no-thread.pdf
  Verify Document Metadata
  ...  document_date=10 augustus 2020
  ...  document_name=MINVWS-4-5144
  ...  document_type=PDF
  ...  document_size=12.95 KB
  ...  document_id=5144
  ...  subjects=Opstart Corona
  ...  judgement=Deels openbaar
  ...  exclusion_grounds=5.1.2e Eerbiediging van de persoonlijke levenssfeer
  Verify Related Document Mentions  case-2-email-with-more-emails-in-thread-1.pdf
  Verify Related Document Mentions  case-2-email-with-more-emails-in-thread-2.pdf
  Get Text  //body  *=  Bijlagen bij dit e-mailbericht
  Verify Listed Attachments In Document  5146  case-5-attachment-multi-1.pdf  3 augustus 2020
  Verify Listed Attachments In Document  5148  case-5-attachment-multi-1.pdf  17 augustus 2020
  Verify Listed Attachments In Document  5167  case-5-attachment-multi-1.pdf  21 augustus 2020
  Verify Document Background Data
  ...  part_of=${EMPTY}
  ...  period=December 2021 - januari 2023
  ...  dossier_type=Woo-verzoek
  ...  dossier_date=${CURRENT_DATE_FORMAT2}
  ...  publication_size=19 documenten, 19 pagina's
  Download File  case-5-mail-with-multi-attachment-no-thread.pdf  xpath=//*[@data-e2e-name="download-file-link"]

Download besluitbrief
  [Documentation]  Locate a existing decision dossier and download and verify the corresponding besluitbrief
  Search For  search_term=Robot ${CURRENT_TIME}
  ...  search_results=Robot ${CURRENT_TIME}
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Download besluit (12.95 KB)
  Download File  decision-1702211972.pdf  xpath=//*[@data-e2e-name="download-decision-file-link"]

Download inventarislijst
  [Documentation]  Locate an existing decision dossier and download and verify the corresponding inventarislijst
  Search For  search_term=Robot ${CURRENT_TIME}
  ...  search_results=Robot ${CURRENT_TIME}
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  19 documenten, 19 pagina's Inventarislijst
  Download File  decision-1702211972.pdf  xpath=//*[@data-e2e-name="download-inventory-file-link"]

Download decision dossier
  [Documentation]  Download a pre-defined decision dossier, check if the file exists and verify the exact filesize of the download
  Click  "Alle gepubliceerde besluiten"
  Click  "Robot ${CURRENT_TIME}"
  Click  xpath=//*[@data-e2e-name="download-documents-button"]
  Get Text  //body  *=  Het archief is gereed voor download
  Get Text  //body  *=  19
  Click Download File Link

Search for non existing document
  [Documentation]  Search for non existing document.
  Search For  search_term=niet_bestaande_document
  ...  search_results=0 documenten in 0 besluiten

Search for word or a partial phrase of the dossier title text
  [Documentation]  Search for word or a partial phrase of the dossier title text.
  Search For  search_term=Robot ${CURRENT_TIME}
  ...  search_results2=Robot ${CURRENT_TIME}

Search for word or a partial phrase of the dossier summary text
  [Documentation]  Search for word or a partial phrase of the dossier summary text
  Search For  search_term=het besluitdossier Robot ${CURRENT_TIME}
  ...  search_results=Samenvatting voor het besluitdossier Robot

Filter decision dossiers
  [Documentation]  Filter the existing decision dossier by daterange
  Click  "Alle gepubliceerde besluiten"
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  # The existing decision dossier has a period of december 2021 - januari 2023
  #  [------------Decision Dossier------------]
  #  [------Filter--------------------------]
  # Date-from 30/11/2021
  # Date-to 30/11/2021
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-11-30&dt%5Bto%5D=2021-11-30
  Get Text  //body  not contains  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  [--------------------------Filter------]
  # Date-from 01/02/2023
  # Date-to 01/02/2023
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2023-02-01&dt%5Bto%5D=2023-02-01
  Get Text  //body  not contains  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  [-------------------------Filter-------]
  # Date-from 31/01/2023
  # Date-to 01/02/2023
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2023-01-31&dt%5Bto%5D=2023-02-01
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  [-------Filter-------------------------]
  # Date-from 31/01/2021
  # Date-to 01/12/2023
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-01-31&dt%5Bto%5D=2023-12-01
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  [----------------Filter----------------]
  # Date-from 01/05/2022
  # Date-to 01/06/2022
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2022-05-01&dt%5Bto%5D=2022-06-01
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  -----------------Filter----------------]
  # Date-from -
  # Date-to 01/06/2022
  Go To  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bto%5D=2022-06-01
  Get Text  //body  *=  Datum tot: 1 juni 2022
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  #  [------------Decision Dossier------------]
  #  [----------------Filter-----------------
  # Date-from 01/05/2022
  # Date-to -
  Go To  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2022-05-01
  Get Text  //body  *=  Robot ${CURRENT_TIME}

Link Zaaknummer to a decision dossier
  [Documentation]  Login to Balie and link a Zaaknummer to a Decision Dossier
  Login Admin
  Click  "Zaken"
  Verify Existence Of Inquiry  11-111
  Verify Existence Of Inquiry  62-487
  Verify Existence Of Inquiry  99-999
  Click  "11-111"
  Verify Inquiry Summary
  ...  decision_name=Robot ${CURRENT_TIME}
  ...  document_summary_string=6 documenten met zaaknummer 11-111 in dit besluit
  Verify Document Listed In Inquiry Overview  case-5-mail-with-multi-attachment-no-thread.pdf
  Verify Document Listed In Inquiry Overview  case-2-email-with-more-emails-in-thread-1.pdf
  Go Back
  Click  xpath=//*[@data-e2e-name="go-to-link-inquires-link"]
  Click  xpath=//*[data-e2e-name="go-to-link-dossiers-link"]
  Fill Text  id=inquiry_link_dossier_form_map  33-66-99
  Click  "+ Kies besluit..."
  Type Text  id=link-dossiers-search-input  Robot ${CURRENT_TIME}  delay=50 ms  clear=Yes
  Click  xpath=//li[contains(@id, 'option-')]
  Click  xpath=//*[@id="js-link-dossier"]
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Click  xpath=//*[@id="inquiry_link_dossier_form_link"]
  Sleep  5s
  Reload
  Get Text  //body  *=  33-66-99
  Click  "33-66-99"
  Verify Decision Listed In Inquiry Overview  Robot ${CURRENT_TIME}
  Go Back
  Logout Admin

Link Zaaknummer to documents
  [Documentation]  Login to Balie and link a Zaaknummer to documents
  Login Admin
  Click  "Zaken"
  Verify Existence Of Inquiry  11-111
  Verify Existence Of Inquiry  62-487
  Verify Existence Of Inquiry  99-999
  Click  "11-111"
  Verify Decision Listed In Inquiry Overview  Robot ${CURRENT_TIME}
  Verify Document Listed In Inquiry Overview  case-5-mail-with-multi-attachment-no-thread.pdf
  Verify Document Listed In Inquiry Overview  case-2-email-with-more-emails-in-thread-1.pdf
  Verify Documents Summary In Inquiry Overview  6 documenten met zaaknummer 11-111
  Go Back
  Click  xpath=//*[@data-e2e-name="go-to-link-inquires-link"]
  Click  xpath=//*[data-e2e-name="go-to-link-documents-link"]
  Upload File By Selector
  ...  xpath=//*[@id="inquiry_link_documents_form_upload"]
  ...  tests/robot_framework/files/koppel_zaaknummer.xlsx
  Click  "Koppelen"
  Get Text  //body  *=  De zaaknummers worden gekoppeld
  Sleep  10s
  Reload
  Verify Existence Of Inquiry  44-444
  Click  "44-444"
  Verify Decision Listed In Inquiry Overview  Robot ${CURRENT_TIME}
  Verify Documents Summary In Inquiry Overview  1 document met zaaknummer 44-444 in dit besluit
  Verify Document Listed In Inquiry Overview  case-6-attachment-for-non-email.pdf
  Sleep  10s
  Click  "Downloaden"
  Click Download File Link
  Go Back
  Go Back
  Logout Admin

Retract a decision dossier
  [Documentation]  Login to Balie and retract the existing decision dossier
  Login Admin
  Search For A Publication  Robot ${CURRENT_TIME}
  Click Button Withdraw All Documents
  Select Withdraw Reason  Document is nog opgeschort
  Provide Withdraw Explanation  Alle documenten in besluitdossier Robot ${CURRENT_EPOCH} worden ingetrokken
  Click  "Intrekken"
  Get Text  //body  *=  Ingetrokken
  Logout Admin
  # Check if all the documents are retracted from the portal
  Click  "Alle gepubliceerde besluiten"
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Click  "Robot ${CURRENT_TIME}"
  Reload
  Verify Dossier Metadata
  ...  dossier_status=Openbaar
  ...  responsible=Ministerie van Volksgezondheid, Welzijn en Sport
  ...  period=December 2021 - januari 2023
  ...  decision_date=${CURRENT_DATE_FORMAT2}
  ...  dossier_type=Woo-verzoek
  ...  publication_size=19 documenten, 19 pagina's
  Verify File History  Alle documenten in dit besluit zijn ingetrokken
  Get Text  //body  not contains  5080
  Get Text  //body  not contains  case-4-mail-with-attachment-thread-1.pdf
  Get Text  //body  not contains  20 augustus 2020
  Get Text  //body  not contains  5044
  Get Text  //body  not contains  case-2-email-with-more-emails-in-thread-2.pdf
  Get Text  //body  not contains  9 oktober 2020
  Get Text  //body  not contains  5146
  Get Text  //body  not contains  case-5-attachment-multi-1.pdf
  Get Text  //body  not contains  3 augustus 2020

Replace Decision Dossier
  [Documentation]  Login to Balie and replace the existing productierapport and some documents
  Login Admin
  Search For A Publication  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Click Document Edit
  Verify Document Listed In File  5080  case-4-mail-with-attachment-thread-1.pdf
  Verify Document Listed In File  5044  case-2-email-with-more-emails-in-thread-2.pdf
  Verify Document Listed In File  5146  case-5-attachment-multi-1.pdf
  Click  "Vervang productierapport"
  Upload File By Selector  id=inventory_inventory  tests/Fixtures/000-inventory-002.xlsx
  Click  "Upload productierapport"
  Sleep  5s
  Get Text  //body  *=  Productierapport geüpload en gecontroleerd
  Get Text  //body  *=  19 bestaande documenten worden aangepast.
  Click Confirm Inventory Replacement
  Sleep  5s
  Get Text  //body  *=  De inventaris is succesvol vervangen.
  # Replace documents
  Click  "Naar documenten"
  Verify Document Listed In File  5080  vervangen_case-4-mail-with-attachment-thread-1.pdf
  Verify Document Listed In File  5044  vervangen_case-2-email-with-more-emails-in-thread-2.pdf
  Verify Document Listed In File  5146  vervangen_case-5-attachment-multi-1.pdf
  Search For A Publication  vervangen_case-4-mail-with-attachment-thread-1.pdf
  Get Text  //body  *=  5080
  Get Text  //body  *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
  Click  "Document vervangen"
  Upload File By Selector  id=replace_form_document  tests/Fixtures/000-documents-001/5080.pdf
  Click  "Vervang document"
  Get Text  //body  *=  Het document wordt nu verwerkt, dit kan even duren.
  Click  "MINVWS-4-5080"
  Get Text  //body  *=  Gepubliceerd
  Get Text  //body  *=  5080
  Get Text  //body  *=  vervangen_case-4-mail-with-attachment-thread-1.pdf
  Click  "Zaken"
  Get Text  //body  *=  11-111
  Get Text  //body  *=  22-222
  Get Text  //body  *=  62-487
  Get Text  //body  *=  99-999
  Reload
  Click  "22-222"
  Get Text  //body  *=  Besluiten en documenten voor zaaknummer 22-222
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Download de inventarislijst zaaknummer 22-222
  Get Text  //body  *=  1 besluitdossier(s) toegevoegd
  Get Text  //body  *=  6 document(en) toegevoegd
  Go Back
  Logout Admin
  # Check if all the documents are replaced on the portal
  Click  "Alle gepubliceerde besluiten"
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Click  "Robot ${CURRENT_TIME}"
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
  Get Text  //body  *=  19 documenten

Filter search results
  [Documentation]  Check the filter results
  Go To  ${BASE_URL}/search?q=
  # Filter by "Type bronbestand"
  Get Checkbox State  id=input_pdf  ==  unchecked
  Check Checkbox  id=input_pdf
  Get Checkbox State  id=input_pdf  ==  checked
  Get Text  //*[@data-e2e-name="search-results"]  contains  vervangen_case-7-attachment-for-non-existing-email.pdf
  Get Text  //*[@data-e2e-name="face-pill"]  contains  Type bronbestand: PDF
  # Filter by "Soort besluit"
  Get Checkbox State  id=input_partial_public  ==  unchecked
  Check Checkbox  id=input_partial_public
  Get Checkbox State  id=input_partial_public  ==  checked
  Get Text  //*[@data-e2e-name="search-results"]  contains  vervangen_case-7-attachment-for-non-existing-email.pdf
  Get Text  //*[@data-e2e-name="face-pill"][2]  contains  Soort besluit: Deels openbaar
  # Filter by "Uitzonderingsgrond"
  Get Checkbox State  id=input_5.1.2i  ==  unchecked
  Check Checkbox  id=input_5.1.2i
  Get Checkbox State  id=input_5.1.2i  ==  checked
  Get Text  //*[@data-e2e-name="search-results"]  contains  vervangen_case-7-attachment-for-non-existing-email.pdf
  Get Text
  ...  //body
  ...  *=
  ...  Uitzonderingsgrond: 5.1.2i Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen
