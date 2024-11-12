*** Settings ***
Documentation       Resource file containing custom keywords for Decision Dossiers in the Balie.
Library             DateTime
Library             Browser
Library             DebugLibrary
Resource            Admin.resource
Resource            Public.resource
Resource            WooDecision.resource


*** Variables ***
${CURRENT_TIME}             ${EMPTY}
${CURRENT_DATE}             ${EMPTY}
${CURRENT_DATE_FORMAT2}     ${EMPTY}
${CURRENT_EPOCH}            ${EMPTY}
${DOSSIER_REFERENCE}        ${EMPTY}
${PREFIX_INDEX}             ${EMPTY}


*** Keywords ***
Fill Out Basic Details
  [Arguments]  ${date_from}=2021-12-01T00:00:00+00:00
  ...  ${date_to}=2023-01-31T00:00:00+00:00
  ...  ${type}=${EMPTY}
  ...  ${prefix}=MINVWS
  Generate Dossier Reference Number
  Fill Text  id=details_title  Robot ${DOSSIER_REFERENCE}
  IF  '${type}' == 'disposition' or '${type}' == 'investigation-report' or '${type}' == 'complaint-judgement'
    Type Text  id=details_date  01012022
  ELSE IF  '${type}' == 'annual-report'
    Select Options By  id=details_year  index  5
  ELSE
    Select Options By  id=details_date_from  value  ${date_from}
    Select Options By  id=details_date_to  value  ${date_to}
  END
  Select Options By  //select[@name="details[departments][0]"]  index  1
  IF  '${type}' == 'woo-decision'
    Check Checkbox  text=Woo-verzoek
    Select Options By  id=details_subject  label  Geen onderwerp
  END
  Select Options By  id=details_documentPrefix_documentPrefix  text  ${prefix}
  Fill Text  id=details_dossierNr  ${DOSSIER_REFERENCE}
  Click  "Opslaan en verder"

Generate Dossier Reference Number
  ${reference_number} =  Generate Random String  3  [NUMBERS][UPPER]
  Set Test Variable  $DOSSIER_REFERENCE  ${reference_number}

Create WooDecision Dossier - CI
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out Decision Details  Openbaarmaking  tests/robot_framework/files/besluitbrief.pdf
  Upload Inventory  tests/Fixtures/000-inventory-001.xlsx
  Verify Document Upload Status  Nog te uploaden: 19 van 19 documenten.
  Upload Document Zip  tests/Fixtures/000-documents-001.7z
  Wait For Elements State  id=js-dossier-documents-completed  visible
  Click Continue To Publish
  Click  "Opslaan en klaarzetten"
  Verify Dossier Confirmation  Openbaar
  Logout Admin

Verify Dossier Confirmation
  [Arguments]  ${dossier_status}
  Get Text  //*[@data-e2e-name="dossier-title"]  *=  Robot ${CURRENT_TIME}
  Get Text  //*[@data-e2e-name="dossier-status"]  *=  ${dossier_status}

Verify Draft Dossier
  Click  "Publicaties"
  Search For A Publication  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Concept
  Get Text  //body  *=  December 2021 - januari 2023
  Get Text  //body  *=  Openbaar
  Get Text  //body  *=  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
  Get Text  //body  *=  besluitbrief.pdf
  Get Text  //body  *=  Niet opgegeven
  Get Text  //body  *=  0 bestanden geüpload
  Get Text  //body  *=  Maak eerst documenten compleet

Verify Published Decision Dossier
  [Documentation]  Used in ci-old
  Click  "Alle gepubliceerde besluiten"
  Sleep  5s
  Reload
  Click  "Robot ${CURRENT_TIME}"
  Verify Dossier Metadata
  ...  dossier_status=Openbaar
  ...  responsible=Ministerie van Volksgezondheid, Welzijn en Sport
  ...  period=December 2021 - januari 2023
  ...  decision_date=${CURRENT_DATE_FORMAT2}
  ...  dossier_type=Woo-verzoek
  ...  publication_size=19 documenten, 19 pagina's
  Verify Listed Document In Dossier  5080  case-4-mail-with-attachment-thread-1.pdf  20 augustus 2020
  Verify Listed Document In Dossier  5044  case-2-email-with-more-emails-in-thread-2.pdf  9 oktober 2020
  Verify Listed Document In Dossier  5146  case-5-attachment-multi-1.pdf  3 augustus 2020

Filter Op Bestuursorgaan En Status
  [Arguments]  ${bestuursorgaan}=  ${status}=
  Click  "Filters"
  Uncheck Checkbox  id=departments_0
  Uncheck Checkbox  id=departments_1
  Uncheck Checkbox  id=departments_2
  Uncheck Checkbox  id=departments_3
  Uncheck Checkbox  id=departments_4
  Uncheck Checkbox  id=departments_5
  Uncheck Checkbox  id=departments_6
  Uncheck Checkbox  id=departments_7
  Uncheck Checkbox  id=departments_8
  Uncheck Checkbox  id=departments_9
  Uncheck Checkbox  id=departments_10
  Uncheck Checkbox  id=departments_11
  IF  $bestuursorgaan == "Ministerie van Algemene Zaken"
    Check Checkbox  id=departments_0
  END
  IF  $bestuursorgaan == "Ministerie van Binnenlandse Zaken en Koninkrijksrelaties"
    Check Checkbox  id=departments_1
  END
  IF  $bestuursorgaan == "Ministerie van Buitenlandse Zaken"
    Check Checkbox  id=departments_2
  END
  IF  $bestuursorgaan == "Ministerie van Defensie"
    Check Checkbox  id=departments_3
  END
  IF  $bestuursorgaan == "Ministerie van Economische Zaken en Klimaat"
    Check Checkbox  id=departments_4
  END
  IF  $bestuursorgaan == "Ministerie van Financiën"
    Check Checkbox  id=departments_5
  END
  IF  $bestuursorgaan == "Ministerie van Infrastructuur en Waterstaat"
    Check Checkbox  id=departments_6
  END
  IF  $bestuursorgaan == "Ministerie van Justitie en Veiligheid"
    Check Checkbox  id=department_7
  END
  IF  $bestuursorgaan == "Ministerie van Landbouw, Natuur en Voedselkwaliteit"
    Check Checkbox  id=department_8
  END
  IF  $bestuursorgaan == "Ministerie van Onderwijs, Cultuur en Wetenschap"
    Check Checkbox  id=departments_9
  END
  IF  $bestuursorgaan == "Ministerie van Sociale Zaken en Werkgelegenheid"
    Check Checkbox  id=departments_10
  END
  IF  $bestuursorgaan == "Ministerie van Volksgezondheid, Welzijn en Sport"
    Check Checkbox  id=departments_11
  END
  Uncheck Checkbox  id=statuses_0
  Uncheck Checkbox  id=statuses_1
  Uncheck Checkbox  id=statuses_2
  Uncheck Checkbox  id=statuses_3
  # Uncheck Checkbox  id=statuses_4
  IF  $status == "Concept"  Check Checkbox  id=statuses_0
  IF  $status == "Publicatie gepland"  Check Checkbox  id=statuses_1
  IF  $status == "Online voor verzoekers"  Check Checkbox  id=statuses_2
  IF  $status == "Openbaar"  Check Checkbox  id=statuses_3
  # IF  ${status} == "Teruggetrokken"  Check Checkbox  id=statuses_4
  Click  //*[@id="submit"]  # Filteren

Verify Dossier Metadata
  [Arguments]  ${dossier_status}  ${responsible}  ${period}  ${decision_date}  ${dossier_type}  ${publication_size}
  Get Text  //*[@data-e2e-name="dossier-metadata-decision"]  *=  ${dossier_status}
  Get Text  //*[@data-e2e-name="dossier-metadata-responsible"]  *=  ${responsible}
  Get Text  //*[@data-e2e-name="dossier-metadata-period"]  *=  ${period}
  Get Text  //*[@data-e2e-name="dossier-metadata-decision-date"]  *=  ${decision_date}
  Get Text  //*[@data-e2e-name="dossier-metadata-publication-reason"]  *=  ${dossier_type}
  Get Text  //*[@data-e2e-name="dossier-metadata-size"]/span  *=  ${publication_size}

Search For A Publication
  [Arguments]  ${search_query}
  Type Text  id=search-previews  ${search_query}  delay=50 ms  clear=Yes
  Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]//*[contains(text(),'${search_query}')]

Click Button Withdraw All Documents
  Click  xpath=//*[@data-e2e-name="withdraw-documents-link"]

Select Withdraw Reason
  [Arguments]  ${withdraw_reason}
  IF  $withdraw_reason == "Ongelakte gegevens in het document"
    Click  id=withdraw_form_reason_0
  ELSE IF  $withdraw_reason == "Ongelakte gegevens in de bestandsnaam"
    Click  id=withdraw_form_reason_1
  ELSE IF  $withdraw_reason == "Document is nog opgeschort"
    Click  id=withdraw_form_reason_2
  ELSE IF  $withdraw_reason == "Document is onleesbaar"
    Click  id=withdraw_form_reason_3
  ELSE IF  $withdraw_reason == "De bijlage hoort niet bij het document"
    Click  id=withdraw_form_reason_4
  END

Provide Withdraw Explanation
  [Arguments]  ${withdraw_explanation}
  Fill Text  id=withdraw_form_explanation  ${withdraw_explanation}

Verify File History
  [Arguments]  ${text}
  Get Text  //*[@id="main-content"]/div/div[4]/div/table  contains  ${text}

Click Documents Edit
  Click  xpath=//*[@data-e2e-name="documents-section"]//*[@data-e2e-name="edit-link"]

Verify Document Listed In File
  [Arguments]  ${document_number}  ${name}
  Get Text  //body  *=  ${document_number}
  Get Text  //body  *=  ${name}

Create New Dossier
  [Arguments]  ${dossier_type}
  Click New Dossier
  Choose Dossier Type  ${dossier_type}

Click New Dossier
  Click  xpath=//*[@data-e2e-name="create-dossier"]

Choose Dossier Type
  [Documentation]  Check if there is an option to choose Convenant or Woo -- This is only shown when you have super-admin permissions.
  [Arguments]  ${dossier_type}=woo-decision
  Click  //*[@data-e2e-name="create-dossier-type-${dossier_type}"]

Publish Dossier And Return To Admin Home
  [Arguments]  ${publication_status}=Gepubliceerd
  IF  "${publication_status}" == "Gepubliceerd"
    Click Save And Prepare
    Click Publications
  ELSE IF  "${publication_status}" == "Gepland"
    ${timestamp} =  Get Current Date
    ${next_year} =  Add Time To Date  ${timestamp}  730 days
    Fill Publication Date  ${next_year}
    Click Save And Prepare
    Click Publications
  ELSE IF  "${publication_status}" == "Concept"
    Click Publications
  END

Click Publications
  Click  "Publicaties"

Click Publication By Value
  [Arguments]  ${value}
  Click  //*[@id="resultaten"]/tbody/tr[td//text()[contains(., '${value}')]]

Verify Publication Confirmation
  [Arguments]  ${preview_date}  ${publication_date}
  Get Text  //*[@data-e2e-name="dossier-preview-date"]  contains  ${preview_date}
  Get Text  //*[@data-e2e-name="dossier-publication-date"]  contains  ${publication_date}
  Get Text  //*[@data-e2e-name="dossier-publication-date-link"]  contains  ${preview_date}
