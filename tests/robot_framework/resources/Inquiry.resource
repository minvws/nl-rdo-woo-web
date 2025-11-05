*** Settings ***
Documentation       Resource file containing custom keywords for inquiries in the admin console.
Library             String
Library             Browser
Library             DebugLibrary
Resource            Dossier.resource


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

Verify Inquiry Dossier
  [Arguments]  ${dossier_id}  ${docs}
  Click  //*[@data-e2e-name="inquiry-dossiers"]//tbody//a[contains(.,'${dossier_id}')]
  VAR  ${published_docs} =  ${docs}[0]
  VAR  ${partially_published_docs} =  ${docs}[1]
  VAR  ${unpublished_docs} =  ${docs}[2]
  VAR  ${not_yet_online_docs} =  ${docs}[3]
  ${nr_of_published_docs} =  Get Length  ${published_docs}
  ${nr_of_partially_published_docs} =  Get Length  ${partially_published_docs}
  ${nr_of_unpublished_docs} =  Get Length  ${unpublished_docs}
  ${nr_of_not_yet_online_docs} =  Get Length  ${not_yet_online_docs}
  Verify Dossier Document Count
  ...  ${nr_of_published_docs}
  ...  ${nr_of_partially_published_docs}
  ...  ${nr_of_unpublished_docs}
  ...  ${nr_of_not_yet_online_docs}
  Verify Inquiry Documents Per Tab  tabs-documenten-content-1  ${published_docs}
  Verify Inquiry Documents Per Tab  tabs-documenten-content-2  ${partially_published_docs}
  Verify Inquiry Documents Per Tab  tabs-documenten-content-3  ${unpublished_docs}
  Verify Inquiry Documents Per Tab  tabs-documenten-content-4  ${not_yet_online_docs}
  Click Public Breadcrumb Element  2

Verify Inquiry Documents Per Tab
  [Arguments]  ${tab_id}  ${document_ids}
  FOR  ${document_id}  IN  @{document_ids}
    ${doc_id_str} =  Convert To String  ${document_id}
    Get Text  //*[@data-e2e-name="${tab_id}"]//tbody  contains  ${doc_id_str}
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
  [Arguments]  ${linking_excel}  ${prefix}=E2E-A
  Upload File By Selector  id=inquiry_link_documents_form_upload  ${linking_excel}
  ${filename} =  Fetch From Right  ${linking_excel}  /
  Wait For Condition  Text  //div[@class="bhr-upload-area__files-area"]  contains  ${filename}
  Select Prefix When Linking Inquiries  ${prefix}
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

Click First Dossier In Inquiry
  Click  (//div[@data-e2e-name="inquiry-dossiers"]//tbody//a)[1]

Click First Document In Inquiry
  Click  (//div[@data-e2e-name="inquiry-documents"]//tbody//a)[1]
