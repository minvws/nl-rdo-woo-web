*** Settings ***
Documentation       Resource file with generic keywords.
Library             DateTime
Library             OperatingSystem
Library             String
Library             Browser
Library             DebugLibrary
Resource            Admin.resource
Resource            Dossier.resource


*** Variables ***
${CURRENT_TIME}         ${EMPTY}
${CURRENT_EPOCH}        ${EMPTY}
${RUN_LOCALLY}          ${EMPTY}
${ATTACHMENT_FILENAME}  ${EMPTY}
${ATTACHMENT_LOCATION}  ${EMPTY}
${INVENTORY}            ${EMPTY}
${DOCUMENTS}            ${EMPTY}
${NUMBER_OF_DOCUMENTS}  ${EMPTY}


*** Keywords ***
Publish Test Dossier
  [Arguments]  ${inventory}  ${documents}  ${number_of_documents}  ${prefix}=MINVWS  ${publication_status}=Gepubliceerd
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=${prefix}
  Fill Out Decision Details  Openbaarmaking
  Upload Inventory  ${inventory}
  Verify Document Upload Remaining  Nog te uploaden: ${number_of_documents} van ${number_of_documents} documenten.
  Upload Document Zip  ${documents}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home  ${publication_status}

Fill Out Decision Details
  [Arguments]  ${decision}  ${has_attachment}=${FALSE}
  Check Checkbox  text="${decision}"
  Fill Text  id=decision_summary  Samenvatting voor het besluitdossier Robot ${CURRENT_TIME}
  Upload File By Selector  id=decision_decision_document  tests/robot_framework/files/woodecision/besluitbrief.pdf
  IF  ${has_attachment}  Upload Attachment  staatscourant
  Click  "Opslaan en verder"

Upload Documents Step
  Upload Inventory  ${INVENTORY}
  Verify Document Upload Remaining  Nog te uploaden: ${NUMBER_OF_DOCUMENTS} van ${NUMBER_OF_DOCUMENTS} documenten.
  Upload Document Zip  ${DOCUMENTS}
  Verify Document Upload Completed
  Click Continue To Publish

Upload Inventory
  [Arguments]  ${inventory_file_location}  ${expect_error}=${FALSE}
  Upload File By Selector  id=inventory_inventory  ${inventory_file_location}
  Click  //*[@id="inventory_submit"]
  IF  not ${expect_error}
    Wait For Elements State  //*[@id="upload-area-dossier-files"]  visible  timeout=30s
  END

Verify Inventory Error
  [Arguments]  ${expected_result}
  Wait For Elements State  //*[@id="inventory-error"]  visible  timeout=30s
  Get Text  //*[@id="inventory-error"]  contains  ${expected_result}

Verify Document Upload Remaining
  [Arguments]  ${expected_result}
  Wait For Condition  Text  //*[@data-e2e-name="missingDocuments"]  contains  ${expected_result}  timeout=20s

Upload Document Zip
  [Arguments]  ${zip_file_location}
  Sleep  30ms  # sometimes the upload doesn't seem to select anything, so let's try a tiny wait here before we click
  Upload File By Selector  id=upload-area-dossier-files  ${zip_file_location}
  Sleep  7s

Verify Document Upload Completed
  Scroll To Element  //*[@id="inhoud"]/div/div[2]
  Wait For Elements State  //*[@id="js-dossier-documents-completed"]  visible  timeout=40s

Click Continue To Publish
  Click  //*[@data-e2e-name="to-next-step-link"]

Click Save And Prepare
  Click  //*[@id="publish_submit"]

Click Replace Report
  Click  //*[@data-e2e-name="replace-report"]

Verify Inventory Replace
  [Arguments]  ${expected_result}
  Wait For Condition  Text  id=js-inventory-status  contains  ${expected_result}  timeout=30s

Click Confirm Inventory Replacement
  Click  //*[@data-e2e-name="confirm-replacement"]

Click Continue To Documents
  Click  //*[@data-e2e-name="continue-to-documents"]

Open Document In Dossier
  [Arguments]  ${document_id}
  Click  "${document_id}"
  Wait For Condition  Text  //*[@data-e2e-name="document-nr"]  contains  ${document_id}

Retract Document
  Click  //*[@data-e2e-name="retract"]
  Check Checkbox  id=withdraw_form_reason_0
  Type Text  //*[@id="withdraw_form_explanation"]  Reden voor intrekken hier
  Click  //*[@id="withdraw_form_submit"]
  Get Text  //*[@id="inhoud"]  contains  Het document is ingetrokken.

Click Breadcrumb Element
  [Arguments]  ${element_nr}
  Click  //*[@id="inhoud"]/div/nav/ol/li[${element_nr}]

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
