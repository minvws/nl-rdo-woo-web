*** Settings ***
Documentation       Resource file containing custom keywords for Decision Dossiers in the Balie.
Library             Browser
Library             DebugLibrary
Resource            Admin.resource


*** Variables ***
${CURRENT_TIME}     ${EMPTY}
${CURRENT_DATE}     ${EMPTY}
${CURRENT_EPOCH}    ${EMPTY}


*** Keywords ***
Create New Decision Dossier
  [Arguments]  ${upload_files}=${FALSE}  ${env}=
  Click  xpath=//*[@data-e2e-name="create-dossier"]
  # Check if there is an option to choose Convenant or Woo
  ${old_timeout} =  Set Browser Timeout  1s
  ${present} =  Run Keyword And Return Status  Get Element  //*[@data-e2e-name="create-dossier-type-woo-decision"]
  IF  ${present}  Click  //*[@data-e2e-name="create-dossier-type-woo-decision"]
  Set Browser Timeout  ${old_timeout}
  # End check
  Fill Text  id=details_title  Robot ${CURRENT_TIME}
  Select Options By  id=details_date_from  value  2021-12-01T00:00:00+00:00
  Select Options By  id=details_date_to  value  2023-01-31T00:00:00+00:00
  Select Options By  id=details_departments  index  0
  Get Checkbox State  id=details_publication_reason_1  ==  checked
  Check Checkbox  id=details_publication_reason_0
  Get Checkbox State  id=details_publication_reason_1  ==  unchecked
  Select Options By  id=details_default_subjects  index  1
  Select Options By  id=details_documentPrefix_documentPrefix  index  1
  Fill Text  id=details_dossierNr  ${CURRENT_EPOCH}
  Click  "Opslaan en verder"
  # Upload besluitbrief
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Check Checkbox  id=decision_decision_2
  Fill Text  id=decision_summary  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
  Upload File By Selector  id=decision_decision_document  tests/robot_framework/files/officiele_besluitbrief.pdf
  Get Text  //body  *=  ${CURRENT_DATE}
  Click  "Opslaan en verder"
  # Upload valid inventarislijst
  Upload File By Selector  id=inventory_inventory  tests/Fixtures/000-inventory-001.xlsx
  Click  xpath=//*[@id="inventory_submit"]
  IF  $env == "test"
    Wait For Condition  Element States  id=inventory-error  contains  visible
    Get Text  //body  *=  documentnummer 5034 bestaat al in een ander dossier
    Get Text  //body  *=  documentnummer 5118 bestaat al in een ander dossier
    Get Text  //body  *=  documentnummer 5291 bestaat al in een ander dossier
    Take Screenshot  fullPage=True
    Click  "Besluitdossiers"
    Search For A Publication  Robot ${CURRENT_TIME}
    Get Text  //body  *=  Robot ${CURRENT_TIME}
    Get Text  //body  *=  December 2021 - januari 2023
    Get Text  //body  *=  Openbaar
    Get Text  //body  *=  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
    Get Text  //body  *=  officiele_besluitbrief.pdf
    Get Text  //body  *=  Niet opgegeven
    Get Text  //body  *=  0 bestanden geüpload
    Get Text  //body  *=  Maak eerst documenten compleet
    Get Text  //body  *=  Besluit verwijderen
    Click  "Besluitdossiers"
  ELSE IF  $env == "ci"
    Wait For Elements State  //*[@id="js-dossier-documents-status"]/div[2]  visible
    Get Text  //body  *=  Nog te uploaden: 19 van 19 documenten.
    IF  ${upload_files}
      Upload File By Selector  id=upload-area-dossier-files  tests/Fixtures/000-documents-001.7z
      Sleep  5s
      Get Text  //body  *=  Uploaden gelukt: Alle documenten uit het productierapport zijn geüpload.
      Click  xpath=//*[@data-e2e-name="to-next-step-link"]
      Click  "Opslaan en klaarzetten"
      Get Text  //body  *=  Robot ${CURRENT_TIME}
      Get Text  //body  *=  December 2021 - januari 2023
      Get Text  //body  *=  officiele_besluitbrief.pdf
      Get Text  //body  *=  19 van 19 bestanden geüpload
      # Check if the correct zaaknummers are displayed
      Get Text  //body  *=  11-111
      Get Text  //body  *=  62-487
      Get Text  //body  *=  99-999
    END
    Logout Balie
    # Check if the newly created decision dossier is displayed on the portal
    Click  "Alle gepubliceerde besluiten"
    Sleep  5s
    Reload
    Click  "Robot ${CURRENT_TIME}"
    Get Text  //body  *=  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
    Get Text  //body  *=  19 documenten, 19 pagina's
    Get Text  //body  *=  5080
    Get Text  //body  *=  case-4-mail-with-attachment-thread-1.pdf
    Get Text  //body  *=  20 augustus 2020
    Get Text  //body  *=  5044
    Get Text  //body  *=  case-2-email-with-more-emails-in-thread-2.pdf
    Get Text  //body  *=  9 oktober 2020
    Get Text  //body  *=  5146
    Get Text  //body  *=  case-5-attachment-multi-1.pdf
    Get Text  //body  *=  3 augustus 2020
  END

Filter Decision Dossier
  [Documentation]  Check the filter functionality in the decision dossier page in the Balie
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Filter Op Bestuursorgaan En Status  status=Concept
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Status: Concept
  Filter Op Bestuursorgaan En Status  status=Concept
  Filter Op Bestuursorgaan En Status  status=Openbaar
  Get Text  //body  not contains  Robot ${CURRENT_TIME}

Delete Concept Decision Dossier
  Search For A Publication  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Robot ${CURRENT_TIME}
  Get Text  //body  *=  Besluit verwijderen
  Click  xpath=//*[@data-e2e-name="delete-dossier-link"]
  Click  xpath=//*[@id="delete_form_submit"]
  Get Text  //body  *=  Het dossier wordt verwijderd, het kan even duren voor dit verwerkt is.
  Sleep  30s
  Click  "Naar overzicht besluitdossiers"

Filter Op Bestuursorgaan En Status
  [Arguments]  ${bestuursorgaan}=  ${status}=
  Click  "Filters"
  Uncheck Checkbox  id=department_0
  Uncheck Checkbox  id=department_1
  Uncheck Checkbox  id=department_2
  Uncheck Checkbox  id=department_3
  Uncheck Checkbox  id=department_4
  Uncheck Checkbox  id=department_5
  Uncheck Checkbox  id=department_6
  Uncheck Checkbox  id=department_7
  Uncheck Checkbox  id=department_8
  Uncheck Checkbox  id=department_9
  Uncheck Checkbox  id=department_10
  Uncheck Checkbox  id=department_11
  IF  $bestuursorgaan == "Ministerie van Algemene Zaken"
    Check Checkbox  id=department_0
  END
  IF  $bestuursorgaan == "Ministerie van Binnenlandse Zaken en Koninkrijksrelaties"
    Check Checkbox  id=department_1
  END
  IF  $bestuursorgaan == "Ministerie van Buitenlandse Zaken"
    Check Checkbox  id=department_2
  END
  IF  $bestuursorgaan == "Ministerie van Defensie"
    Check Checkbox  id=department_3
  END
  IF  $bestuursorgaan == "Ministerie van Economische Zaken en Klimaat"
    Check Checkbox  id=department_4
  END
  IF  $bestuursorgaan == "Ministerie van Financiën"
    Check Checkbox  id=department_5
  END
  IF  $bestuursorgaan == "Ministerie van Infrastructuur en Waterstaat"
    Check Checkbox  id=department_6
  END
  IF  $bestuursorgaan == "Ministerie van Justitie en Veiligheid"
    Check Checkbox  id=department_7
  END
  IF  $bestuursorgaan == "Ministerie van Landbouw, Natuur en Voedselkwaliteit"
    Check Checkbox  id=department_8
  END
  IF  $bestuursorgaan == "Ministerie van Onderwijs, Cultuur en Wetenschap"
    Check Checkbox  id=department_9
  END
  IF  $bestuursorgaan == "Ministerie van Sociale Zaken en Werkgelegenheid"
    Check Checkbox  id=department_10
  END
  IF  $bestuursorgaan == "Ministerie van Volksgezondheid, Welzijn en Sport"
    Check Checkbox  id=department_11
  END
  Uncheck Checkbox  id=status_0
  Uncheck Checkbox  id=status_1
  Uncheck Checkbox  id=status_2
  Uncheck Checkbox  id=status_3
  # Uncheck Checkbox  id=status_4
  IF  $status == "Concept"  Check Checkbox  id=status_0
  IF  $status == "Publicatie gepland"  Check Checkbox  id=status_1
  IF  $status == "Online voor verzoekers"  Check Checkbox  id=status_2
  IF  $status == "Openbaar"  Check Checkbox  id=status_3
  # IF  ${status} == "Teruggetrokken"  Check Checkbox  id=status_4
  Click  //*[@id="submit"]  # Filteren

Verify Decision Dossier Metadata
  [Arguments]  ${dossier_status}  ${responsible}  ${period}  ${decision_date}  ${dossier_type}  ${publication_size}
  Get Text  //*[@data-e2e-name="dossier-metadata-decision"]  *=  ${dossier_status}
  Get Text  //*[@data-e2e-name="dossier-metadata-responsible"]  *=  ${responsible}
  Get Text  //*[@data-e2e-name="dossier-metadata-period"]  *=  ${period}
  Get Text  //*[@data-e2e-name="dossier-metadata-decision-date"]  *=  ${decision_date}
  Get Text  //*[@data-e2e-name="dossier-metadata-publication-reason"]  *=  ${dossier_type}
  Get Text  //*[@data-e2e-name="dossier-metadata-size"]/span  *=  ${publication_size}

Verify Document Metadata
  [Arguments]
  ...  ${document_date}
  ...  ${document_name}
  ...  ${document_type}
  ...  ${document_size}
  ...  ${document_id}
  ...  ${subjects}
  ...  ${judgement}
  ...  ${exclusion_grounds}
  Get Text  //body  *=  ${document_date}
  Get Text  //body  *=  ${document_name}
  Get Text  //body  *=  ${document_type}
  Get Text  //body  *=  ${document_size}
  Get Text  //body  *=  ${document_id}
  Get Text  //body  *=  ${subjects}
  Get Text  //body  *=  ${judgement}
  Get Text  //body  *=  ${exclusion_grounds}

Verify Document Background Data
  [Arguments]  ${part_of}  ${period}  ${dossier_type}  ${dossier_date}  ${publication_size}
  Get Text  //body  *=  ${part_of}
  Get Text  //body  *=  ${period}
  Get Text  //body  *=  ${dossier_type}
  Get Text  //body  *=  ${dossier_date}
  Get Text  //body  *=  ${publication_size}

Verify Related Document Mentions
  [Arguments]  ${related_document}
  Get Text  //body  *=  ${related_document}

Search For A Publication
  [Arguments]  ${search_query}
  Type Text  id=search-previews  ${search_query}  delay=50 ms  clear=Yes
  Click  xpath=//*[@data-e2e-name="search-previews-results"]//*[@data-e2e-name="search-previews-result"][1]/td[2]

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

Click Button Edit
  Click  xpath=//*[@data-e2e-name="documents-section"]//*[@data-e2e-name="edit-link"]

Verify Document Listed In File
  [Arguments]  ${document_number}  ${name}
  Get Text  //body  *=  ${document_number}
  Get Text  //body  *=  ${name}
