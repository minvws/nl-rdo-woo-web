*** Settings ***
Documentation       Resource file with generic keywords.
Library             OperatingSystem
Library             String
Library             Browser
Library             DebugLibrary


*** Variables ***
${CURRENT_TIME}     ${EMPTY}
${CURRENT_EPOCH}    ${EMPTY}


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
  [Arguments]  ${inventory_file_location}
  Upload File By Selector  id=inventory_inventory  ${inventory_file_location}
  Click  xpath=//*[@id="inventory_submit"]

Verify Inventory Error
  [Arguments]  ${expected_result}
  Get Text  //*[@id="inventory-error"]  contains  ${expected_result}

Upload Document Zip
  [Arguments]  ${zip_file_location}
  Upload File By Selector  id=upload-area-dossier-files  ${zip_file_location}
  # TODO: Replace with proper waits, but it's tricky with how the UI progresses after a complete and a partial upload
  Sleep
  ...  5s

Verify Document Upload Status
  [Arguments]  ${expected_result}
  Get Text  //*[@id="js-dossier-documents-status"]  contains  ${expected_result}

Click Continue To Publish
  Click  //*[@data-e2e-name="to-next-step-link"]

Click Save And Prepare
  Click  //*[@id="publish_submit"]

Click Replace Report
  Click  //*[@data-e2e-name="replace-report"]

Verify Inventory Replace
  [Arguments]  ${expected_result}
  Wait For Condition  Text  id=js-inventory-status  not contains  Verwerking gaat beginnen
  Get Text  //*[@id="js-inventory-status"]  contains  ${expected_result}

Click Confirm Inventory Replacement
  Click  //*[@data-e2e-name="confirm-replacement"]

Click Continue To Documents
  Click  //*[@data-e2e-name="continue-to-documents"]
