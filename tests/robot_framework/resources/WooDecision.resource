*** Settings ***
Documentation       Resource file containing custom keywords for the WooDecision information category
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Admin.resource
Resource            Dossier.resource
Resource            TestData.resource


*** Variables ***
${ENVIRONMENT}          ${EMPTY}
${PRODUCTION_REPORT}    ${EMPTY}
${DOCUMENTS}            ${EMPTY}
${NUMBER_OF_DOCUMENTS}  ${EMPTY}


*** Keywords ***
Publish Test WooDecision
  [Arguments]
  ...  ${production_report}
  ...  ${documents}
  ...  ${number_of_documents}
  ...  ${prefix}=E2E-A
  ...  ${publication_status}=Gepubliceerd
  Create New Dossier  woo-decision
  Fill Out Basic Details  prefix=${prefix}  type=woo-decision
  Fill Out WooDecision Details  Openbaarmaking
  Upload Production Report  ${production_report}
  Verify Document Upload Remaining  Nog te uploaden: ${number_of_documents} van ${number_of_documents} document
  Upload And Process Documents  ${documents}
  Verify Document Upload Completed
  Click Continue To Publish
  Publish Dossier And Return To Admin Home  ${publication_status}
  Log  Dossier reference: ${DOSSIER_REFERENCE}

Publish Generated Test WooDecision
  [Arguments]
  ...  ${prefix}=E2E-A
  ...  ${publication_status}=Gepubliceerd
  Generate Test Data Set  woo-decision
  Publish Test WooDecision
  ...  production_report=${PRODUCTION_REPORT}
  ...  documents=${DOCUMENTS}
  ...  number_of_documents=${NUMBER_OF_DOCUMENTS}
  ...  prefix=${prefix}
  ...  publication_status=${publication_status}

Fill Out WooDecision Details
  [Arguments]  ${decision}  ${has_attachment}=${FALSE}
  Check Checkbox  text="${decision}"
  ${random_summary} =  Fakerlibrary.Text  200
  Fill Text  id=decision_summary  ${random_summary}
  Click  " Besluitbrief toevoegen... "
  Upload Besluitbrief  files/woodecision/besluitbrief.txt
  IF  ${has_attachment}  Upload Attachment  staatscourant
  Click  "Opslaan en verder"

Upload Besluitbrief
  [Arguments]  ${besluitbrief_location}
  ${_}  ${besluitbrief_filename} =  Split Path  ${besluitbrief_location}
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${besluitbrief_location}
  Wait For Condition  Text  //dialog[@open]//div[@class="bhr-file"]  contains  ${besluitbrief_filename}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Success Alert Is Visible  Besluitbrief '${besluitbrief_filename}' is

Upload Documents Step
  Upload Production Report  ${PRODUCTION_REPORT}
  Verify Document Upload Remaining  Nog te uploaden: ${NUMBER_OF_DOCUMENTS} van ${NUMBER_OF_DOCUMENTS} documenten.
  Upload And Process Documents  ${DOCUMENTS}
  Verify Document Upload Completed
  Click Continue To Publish

Upload Production Report
  [Arguments]  ${production_report_location}  ${expect_error}=${FALSE}
  Upload File By Selector  id=inventory_inventory  ${production_report_location}
  Click Save And Continue Production Report
  Wait For Elements State
  ...  //*[@id="js-inventory-status"]//*[contains(.,"Bezig met het toepassen van het productierapport")]
  ...  detached
  IF  not ${expect_error}
    Wait For Elements State
    ...  //*[@id="upload-area-dossier-files"]
    ...  visible
    ...  message=Error(s) while uploading production report
  END

Verify Production Report Error
  [Arguments]  ${expected_result}
  Get Text  //*[@id="inventory-error"]  contains  ${expected_result}

Verify Document Upload Remaining
  [Arguments]  ${expected_result}
  Wait For Condition  Text  //h3[@data-e2e-name="missingDocuments"]  contains  ${expected_result}

Upload And Process Documents
  [Arguments]  @{file_locations}
  # This has been wrapped with a retry mechanism, since sometimes the Click Process Documents fails for unclear reasons
  Wait Until Keyword Succeeds  3x  20s  Inner Keyword Upload And Process Documents  @{file_locations}

Inner Keyword Upload And Process Documents
  [Arguments]  @{file_locations}
  Sleep  30ms  # sometimes the upload doesn't seem to select anything, so let's try a tiny wait here before we click
  Scroll To Element  //button[@data-e2e-name="process-documents"]
  Upload File By Selector  id=upload-area-dossier-files  @{file_locations}
  Wait For Elements State  //*[@data-e2e-name="uploaded-files"]  visible
  Click Process Documents
  Wait For Elements State  //*[@data-e2e-name="checking"]  detached
  Wait For Elements State  //*[@data-e2e-name="upload-busy"]  detached

Verify Document Upload Completed
  Scroll To Element  //*[@id="inhoud"]/div/div[2]
  Wait For Elements State  //*[@data-e2e-name="upload-completed"]  attached

Click Replace Report
  Click  //*[@data-e2e-name="replace-report"]

Verify Production Report Replace
  [Arguments]  ${expected_result}
  Wait For Condition  Text  id=js-inventory-status  contains  ${expected_result}

Click Confirm Production Report Replacement
  Click  //*[@data-e2e-name="confirm-replacement"]

Click Continue To Documents
  Click  //*[@data-e2e-name="continue-to-documents"]

Click Process Documents
  Wait For Load State  load
  Click  //button[@data-e2e-name="process-documents"]
  Wait For Elements State
  ...  //button[@data-e2e-name="process-documents"]
  ...  detached
  ...  message=Click on Process Documents did not work

Open Document In Dossier
  [Arguments]  ${document_id}
  Scroll To Element  //table[@data-e2e-name="dossier-documents"]
  Click  //*[@data-e2e-name="doc-link"][contains(.,'${document_id}')]
  Wait For Condition  Text  //*[@data-e2e-name="document-nr"]  contains  ${document_id}

Retract Document
  Click  //*[@data-e2e-name="retract"]
  Check Checkbox  id=withdraw_document_form_reason_0
  Type Text  //*[@id="withdraw_document_form_explanation"]  Reden voor intrekken hier
  Click  //*[@id="withdraw_document_form_submit"]
  Get Text  //*[@id="inhoud"]  contains  Het document wordt nu ingetrokken. Het kan even duren voor dit verwerkt is.

Click Breadcrumb Element
  [Arguments]  ${element_nr}
  Click  (//*[@data-e2e-name="breadcrumb"])[${element_nr}]

Click Public URL
  Click  //*[@data-e2e-name="public-url"]

Verify Document Details
  [Arguments]  ${download_type}  ${publication_status}
  Reload
  Get Text  //*[@data-e2e-name="download-type"]  contains  ${download_type}
  Get Text  //*[@data-e2e-name="publication-status"]  contains  ${publication_status}

Danger Zone Withdraw All Documents
  Click  //*[@data-e2e-name="withdraw-documents-link"]
  Check Checkbox  id=withdraw_document_form_reason_0
  Type Text  //*[@id="withdraw_document_form_explanation"]  Reden voor intrekken hier
  Click  //*[@id="withdraw_document_form_submit"]
  Get Text
  ...  //*[@id="inhoud"]
  ...  contains
  ...  Alle (deels) openbare documenten worden nu ingetrokken. Het kan even duren voor dit verwerkt is.

Fill Publication Date
  [Arguments]  ${date}
  ${day} =  Convert Date  ${date}  result_format=%d
  ${month} =  Convert Date  ${date}  result_format=%m
  ${year} =  Convert Date  ${date}  result_format=%Y
  IF  '${ENVIRONMENT}' == 'docker-local'
    Type Text  id=publish_publication_date  ${day}${month}${year}
  ELSE
    Type Text  id=publish_publication_date  ${month}${day}${year}
  END

Click Edit Decision
  Click  //a[@data-e2e-name="edit-decision"]

Click Save And Continue Production Report
  Click  //*[@id="inventory_submit"]
