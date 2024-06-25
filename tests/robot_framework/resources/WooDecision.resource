*** Settings ***
Documentation       Resource file with generic keywords.
Library             OperatingSystem
Library             String
Library             Browser
Library             DebugLibrary
Library             DateTime


*** Variables ***
${CURRENT_TIME}     ${EMPTY}
${CURRENT_EPOCH}    ${EMPTY}
${RUN_LOCALLY}      ${EMPTY}


*** Keywords ***
Fill Out Basic Details
  [Documentation]  Stores the reference number in suite var $DOSSIER_REFERENCE
  [Arguments]  ${date_from}=2021-12-01T00:00:00+00:00  ${date_to}=2023-01-31T00:00:00+00:00
  Fill Text  id=details_title  Robot ${CURRENT_TIME}
  Select Options By  id=details_date_from  value  ${date_from}
  Select Options By  id=details_date_to  value  ${date_to}
  Select Options By  id=details_departments  index  0
  Check Checkbox  text=Woo-verzoek
  Select Options By  id=details_default_subjects  label  Opstart Corona
  Select Options By  id=details_documentPrefix_documentPrefix  index  1
  ${reference_number} =  Generate Random String  3  [NUMBERS][UPPER]
  Fill Text  id=details_dossierNr  ${reference_number}
  Set Suite Variable  $DOSSIER_REFERENCE  ${reference_number}
  Click  "Opslaan en verder"

Upload Inventory
  [Arguments]  ${inventory_file_location}  ${expect_error}=${FALSE}
  Upload File By Selector  id=inventory_inventory  ${inventory_file_location}
  Click  //*[@id="inventory_submit"]
  IF  ${expect_error} == ${FALSE}
    Wait For Elements State  //*[@id="upload-area-dossier-files"]  visible  timeout=30s
  END

Verify Inventory Error
  [Arguments]  ${expected_result}
  Get Text  //*[@id="inventory-error"]  contains  ${expected_result}

Verify Document Upload Remaining
  [Arguments]  ${expected_result}
  Wait For Condition  Text  //*[@data-e2e-name="missingDocuments"]  contains  ${expected_result}

Upload Document Zip
  [Arguments]  ${zip_file_location}
  Upload File By Selector  id=upload-area-dossier-files  ${zip_file_location}
  Sleep  7s

Verify Document Upload Completed
  Wait For Elements State  //*[@id="js-dossier-documents-completed"]  visible  timeout=10s

Click Continue To Publish
  Click  //*[@data-e2e-name="to-next-step-link"]

Click Save And Prepare
  Click  //*[@id="publish_submit"]

Click Replace Report
  Click  //*[@data-e2e-name="replace-report"]

Verify Inventory Replace
  [Arguments]  ${expected_result}
  Wait For Condition  Text  id=js-inventory-status  contains  ${expected_result}  timeout=20s

Click Confirm Inventory Replacement
  Click  //*[@data-e2e-name="confirm-replacement"]

Click Continue To Documents
  Click  //*[@data-e2e-name="continue-to-documents"]

Open Document In Dossier
  [Arguments]  ${document_id}
  Click  "${document_id}"

Retract Document
  Click  //*[@data-e2e-name="retract"]
  Check Checkbox  id=withdraw_form_reason_0
  Type Text  //*[@id="withdraw_form_explanation"]  Reden voor intrekken hier
  Click  //*[@id="withdraw_form_submit"]
  Get Text  //*[@id="inhoud"]  contains  Het document is ingetrokken.

Click Breadcrumb Element 3
  Click  //*[@id="inhoud"]/div/nav/ol/li[3]

Click Public URL
  Click  //*[@data-e2e-name="public-url"]

Verify Document Details
  [Arguments]  ${download_type}  ${publication_status}
  Get Text  //*[@data-e2e-name="download-type"]  contains  ${download_type}
  Get Text  //*[@data-e2e-name="publication-status"]  contains  ${publication_status}

Danger Zone Withdraw All Documents
  Click  //*[@data-e2e-name="withdraw-documents-link"]
  Check Checkbox  id=withdraw_form_reason_0
  Type Text  //*[@id="withdraw_form_explanation"]  Reden voor intrekken hier
  Click  //*[@id="withdraw_form_submit"]

Fill Publication Date
  [Arguments]  ${date}
  ${day} =  Convert Date  ${date}  result_format=%d
  ${month} =  Convert Date  ${date}  result_format=%m
  ${year} =  Convert Date  ${date}  result_format=%Y
  IF  ${RUN_LOCALLY}
    Type Text  id=publish_publication_date  ${day}${month}${year}
  ELSE
    Type Text  id=publish_publication_date  ${month}${day}${year}
  END
