*** Settings ***
Documentation       Resource file containing custom keywords for inquiries in the admin console.
Library             String
Library             Browser
Library             DebugLibrary
Resource            Generic.resource


*** Keywords ***
Click Inquiries
  Click  " Zaken "

Open Inquiry
  [Arguments]  ${inquiry_id}
  Wait For Load State  networkidle
  Click With Reload Retry  //*[@data-e2e-name="inquiry-list"]//a[contains(.,'${inquiry_id}')]

Verify Inquiry Dossiers
  [Arguments]  ${dossier_ids}
  FOR  ${dossier_id}  IN  @{dossier_ids}
    Get Text  //*[@data-e2e-name="inquiry-dossiers"]  contains  ${dossier_id}
  END

Verify Inquiry Info
  [Arguments]  ${nr_of_published_docs}=0  ${nr_of_partially_published_docs}=0  ${nr_of_unpublished_docs}=0
  IF  ${nr_of_published_docs} == 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_published_docs} document is hierna openbaar gemaakt
  ELSE IF  ${nr_of_published_docs} > 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_published_docs} documenten zijn hierna openbaar gemaakt
  END
  IF  ${nr_of_partially_published_docs} == 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_partially_published_docs} document is hierna gedeeltelijk openbaar gemaakt.
  ELSE IF  ${nr_of_partially_published_docs} > 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_partially_published_docs} documenten zijn hierna gedeeltelijk openbaar gemaakt.
  END
  IF  ${nr_of_unpublished_docs} == 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_unpublished_docs} document wordt niet openbaar gemaakt.
  ELSE IF  ${nr_of_unpublished_docs} > 1
    Get Text
    ...  //*[@data-e2e-name="inquiry-info"]
    ...  contains
    ...  ${nr_of_unpublished_docs} documenten wordt niet openbaar gemaakt.
  END

Verify Inquiry Documents
  [Arguments]  ${document_ids}
  FOR  ${document_id}  IN  @{document_ids}
    Get Text  //*[@data-e2e-name="inquiry-documents"]  contains  ${document_id}
  END

Click Manual Inquiry Linking
  Click  //*[@data-e2e-name="go-to-link-inquires-link"]

Click Manual Woo Document Linking
  Click  //*[@data-e2e-name="go-to-link-documents-link"]

Click Manual Woo Decision Linking
  Click  //*[@data-e2e-name="go-to-link-dossiers-link"]

Link Inquiry To Decision
  [Arguments]  ${inquiry}  ${decision}
  Fill Text  //input[@id="inquiry_link_dossier_form_map"]  ${inquiry}
  Click  //*[@data-e2e-name="inquiry-decision-selector"]
  Fill Text  //input[@data-e2e-name="dossier-search-results"]  ${decision}
  Click  //div[@data-e2e-name="search-previews-results"]//li[1]
  Click  //button[@data-e2e-name="link-dossier"]
  Click  //button[@id="inquiry_link_dossier_form_link"]

Link Inquiry To Documents
  [Arguments]  ${linking_excel}
  Upload File By Selector  id=inquiry_link_documents_form_upload  ${linking_excel}
  ${filename} =  Fetch From Right  ${linking_excel}  /
  Wait For Condition  Text  //div[@class="bhr-upload-area__files-area"]  contains  ${filename}
  Select Prefix When Linking Inquiries  E2E-A
  Click  id=inquiry_link_documents_form_link
  Success Alert Is Visible  zaaknummers gekoppeld
  Click  id=inquiry_link_documents_form_cancel

Select Prefix When Linking Inquiries
  [Arguments]  ${prefix}
  ${old_timeout} =  Set Browser Timeout  1
  ${present} =  Run Keyword And Return Status
  ...  Get Element States
  ...  id=inquiry_link_documents_form_prefix
  ...  contains
  ...  visible
  IF  ${present}
    Select Options By  id=inquiry_link_documents_form_prefix  text  ${prefix}
  END
  Set Browser Timeout  ${old_timeout}

Download Inquiry Archive
  Generic Download Click  //*[@data-e2e-name="download-documents-button"]  expected_extension=zip

Click First Document In Inquiry
  Click  (//div[@data-e2e-name="inquiry-documents"]//tbody//a)[1]
