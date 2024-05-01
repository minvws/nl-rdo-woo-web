*** Settings ***
Documentation       End-to-end tests for the Staging/Acceptance environment.
...                 These are tailored as to not change the database state and should therefore not be executed on Test.
Resource            resources/Setup.resource
Resource            resources/Generic.resource
Resource            resources/Public.resource
Suite Setup         Test Suite Setup
Test Tags           e2e_acc  e2e  acc


*** Variables ***
${BASE_URL}         https://${ACC_USER}:${ACC_PASSWORD}@web.acc.woo.rdobeheer.nl
${ACC_USER}         %{USERNAME_WOO_STAGING}
${ACC_PASSWORD}     %{PASSWORD_WOO_STAGING}


*** Test Cases ***
Basic search
  [Documentation]  Do a basic search and check if it returns results
  Search For  search_term=notulen
  ...  search_results=notulen

Search returns search suggestions
  [Documentation]  Do a basic search with a typo (DocumAnten instead of DocumEnten) and check if Woo returns search suggestions
  Search For  search_term=documanten
  ...  search_results=0 documenten in 0 besluiten
  ${suggest_search_element} =  Get Element  //a[normalize-space()='documenten']
  Should Contain  ${suggest_search_element}  documenten
  Get Text  //body  *=  We konden geen documenten vinden die passen bij uw zoekopdracht "documanten".
  Get Text  //body  *=  Bedoelde u misschien een van de volgende zoektermen: documenten

Decision Dossier overview page
  [Documentation]  Locate an existing decision dossier, check if the predefined metadata are available, the numbers match and expected documents are shown
  Search For  search_term=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  search_results=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  Get Text  //body  *=  Gepubliceerd op: 29 augustus 2023
  Get Text  //body  *=  Besluitnummer: VWS-561-2639
  Get Text
  ...  //body
  ...  *=
  ...  De minister van Volksgezondheid, Welzijn en Sport heeft op 25 april 2023 een besluit genomen op verzoeken in het kader van de Wet open overheid. Het besluit betreft openbaarmaking van documenten die betrekking hebben op informatie aangaande Overleg VWS over de periode mei 2021.
  Verify Decision Dossier Metadata
  ...  dossier_status=Deels openbaar
  ...  responsible=Ministerie van Volksgezondheid, Welzijn en Sport
  ...  period=Mei 2021
  ...  decision_date=29 augustus 2023
  ...  dossier_type=Woo-verzoek
  ...  publication_size=792 documenten, 1.815 pagina's
  # First page
  Verify Listed Document In Dossier  701061  Dagstart bespreeklijst Toegangstesten.docx.docx  14 mei 2021
  Verify Listed Document In Dossier  701841  4.1. Notitie - Verdelingssystematiek Toegangstesten.pdf  25 mei 2021
  # Last page
  Click  xpath=//*[@data-e2e-name="tab1"]//*[@data-e2e-name="page-number-5"]
  Verify Listed Document In Dossier  704722  Verslag MT MEVA 20210504.pdf  12 mei 2021
  Verify Listed Document In Dossier  752554  20210525 Powerpoint TCO.pptx.pdf  25 mei 2021
  # Navigate to the documents that are not made public
  Click  xpath=//*[@data-e2e-name="tab-button-2"]
  Sleep  1s
  Click  xpath=//*[@data-e2e-name="tab-button-2"]
  # Search the first document visible on the first page
  Sleep  3s
  Verify Listed Document In Dossier  701117  Annotatie tbv ACC - DCC - 25 mei 2021.docx.docx  24 mei 2021
  # Search the last document visible on the first page
  Verify Listed Document In Dossier  701803  2. Concept verslag DOC-19 18 mei 2021.docx  21 mei 2021
  # Navigate to the fifth and last page
  Click  xpath=//*[@data-e2e-name="tab2"]//*[@data-e2e-name="page-number-4"]
  # Search the first document visible on the last page
  Verify Listed Document In Dossier
  ...  704016
  ...  Geannoteerde agenda ACC-19 6 mei 2021 agendapunt 7 Voortgangsrapportagearchivering hotspot Covid-19 (stuk bijgevoegd DGSC-19).docx
  ...  6 mei 2021
  # Search the last document visible on the last page
  Verify Listed Document In Dossier  704837  Vooruitblik week 39.xlsx  27 september 2021

Document overview page (document is made public)
  [Documentation]  Locate a existing document, check if the predefined metadata are available, document can be downloaded.
  Search For  search_term=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  search_results=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Click  "Zoeken in deze documenten..."
  Get Text  //body  *=  792 documenten in 1 besluit
  # search by documentnumber
  Search For  search_term=701061
  ...  search_results=701061
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Reload
  Get Text  //h1  *=  Dagstart bespreeklijst Toegangstesten.docx.docx
  Verify Document Metadata
  ...  document_date=14 mei 2021
  ...  document_name=VWS-WOO-701061
  ...  document_type=Word-document
  ...  document_size=249.7 KB
  ...  document_id=701061
  ...  subjects=Overleg
  ...  judgement=Deels openbaar
  ...  exclusion_grounds=5.1.2e Eerbiediging van de persoonlijke levenssfeer
  Verify Document Background Data
  ...  part_of=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  period=Mei 2021
  ...  dossier_type=Woo-verzoek
  ...  dossier_date=29 augustus 2023
  ...  publication_size=792 documenten, 1.815 pagina's
  Download File  Dagstart bespreeklijst Toegangstesten.docx.docx  "Downloaden (PDF 249.7 KB)"

Document overview page (document is NOT made public)
  [Documentation]  Locate a existing document that is NOT made public, check if the predefined metadata are available
  Go To  ${BASE_URL}/dossier/VWS-561-2639/document/VWS-WOO-701199
  Get Text  //h1  *=  Verslag webinar RIVM en het coronavirus.SLGTSL.docx
  Verify Document Metadata
  ...  document_date=6 mei 2021
  ...  document_name=VWS-WOO-701199
  ...  document_type=Word-document
  ...  document_size=${EMPTY}
  ...  document_id=701199
  ...  subjects=Overleg
  ...  judgement=Niet openbaar
  ...  exclusion_grounds=5.1.2i Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen
  Verify Document Background Data
  ...  part_of=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  period=Mei 2021
  ...  dossier_type=Woo-verzoek
  ...  dossier_date=29 augustus 2023
  ...  publication_size=792 documenten, 1.815 pagina's
  # Some checks to make sure download options are NOT available
  Get Text  //body  not contains  Klik op een pagina om de PDF
  Get Text  //body  not contains  Downloaden

Download besluitbrief
  [Documentation]  Locate an existing decision dossier and download and verify the corresponding besluitbrief
  Search For  search_term=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  search_results=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  Get Text  //body  *=  Download besluit (881.67 KB)
  Download File  "decision-VWS-561-2639.pdf"  "Download besluit "

Download inventarislijst
  [Documentation]  Locate a existing decision dossier and download and verify the corresponding inventarislijst
  Search For  search_term=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  ...  search_results=Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  # The search term is the exact match of the decision dossier. The first result should be the decision dossier.
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Get Text  //h1  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  Get Text  //body  *=  792 documenten, 1.815 pagina's Inventarislijst
  Download File  "inventarislijst-VWS-561-2639.xlsx"  "Inventarislijst"

Download small decision dossier
  [Documentation]  Download a small (<30MB) pre-defined decision dossier, check if the file exists and verify the exact filesize of the download
  Search For  search_term=Besluit op uw Wob-verzoek inzake de financiële steun die het kabinet heeft verleend aan KLM
  ...  search_results=Besluit op uw Wob-verzoek inzake de financiële steun die het kabinet heeft verleend aan KLM
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Click  xpath=//*[@data-e2e-name="download-documents-button"]
  Get Text  //body  *=  Download document archief
  Get Text  //body  *=  Het archief is gereed voor download
  Get Text  //body  *=  88
  ${filename} =  Get Text  xpath=//*[@data-e2e-name="file-name"]
  Download File  ${filename}  xpath=//*[@data-e2e-name="download-file-link"]

Download large decision dossier
  [Documentation]  Download a large (>1GB) pre-defined decision dossier, check if the file exists and verify the exact filesize of the download
  IF  ${RUN_LOCALLY}  Skip
  Search For  search_term=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
  ...  search_results=Woo-deelbesluit aangaande Scenario’s en maatregelen over de periode september 2020
  Click  xpath=//*[@data-e2e-name="search-result"][1]//*[@data-e2e-name="main-link"]
  Click  xpath=//*[@data-e2e-name="download-documents-button"]
  Get Text  //body  *=  Download document archief
  Get Text  //body  *=  Het archief is gereed voor download
  Get Text  //body  *=  3767
  ${filename} =  Get Text  xpath=//*[@data-e2e-name="file-name"]
  Download File  ${filename}  xpath=//*[@data-e2e-name="download-file-link"]

Filter decision dossiers
  [Documentation]  Filter the existing decision dossier by daterange
  # The existing decision dossier has a period of may 2021
  #  [------------Decision Dossier------------]
  #  [------Filter--------------------------]
  # Date-from 29/04/2021
  # Date-to 30/04/2021
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-04-29&dt%5Bto%5D=2021-04-30
  Get Text  //body  not contains  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  #  [------------Decision Dossier------------]
  #  [--------------------------Filter------]
  # Date-from 01/06/2023
  # Date-to 02/06/2023
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-06-01&dt%5Bto%5D=2021-06-02
  Get Text  //body  not contains  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  #  [------------Decision Dossier------------]
  #  [-------------------------Filter-------]
  # Date-from 31/05/2021
  # Date-to 02/06/2022
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-05-31&dt%5Bto%5D=2021-06-02
  Get Text  //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  #  [------------Decision Dossier------------]
  #  [-------Filter-------------------------]
  # Date-from 30/04/2021
  # Date-to 01/05/2021
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-04-30&dt%5Bto%5D=2021-05-01
  Get Text  //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  #  [------------Decision Dossier------------]
  #  [----------------Filter----------------]
  # Date-from 01/05/2021
  # Date-to 31/05/2021
  Go To
  ...  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bfrom%5D=2021-05-01&dt%5Bto%5D=2021-05-31
  Get Text  //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
  #  [------------Decision Dossier------------]
  #  -----------------Filter----------------]
  # Date-from -
  # Date-to 31/05/2021
  Go To  ${BASE_URL}/search?type=dossier&sort=decision_date&sortorder=desc&dt%5Bto%5D=2021-05-31
  Get Text  //body  *=  Woo-deelbesluit aangaande Overleg VWS over de periode mei 2021
