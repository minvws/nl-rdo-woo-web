*** Settings ***
Documentation       Keywords related to document pages on public.
Library             Browser
Library             DebugLibrary


*** Keywords ***
Verify Listed Attachments In Document
  [Arguments]  ${document_number}  ${name}  ${date}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${document_number}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${name}
  Get Text  //*[@data-e2e-name="documents-section"]  *=  ${date}

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
  Get Text  //*[@data-e2e-name="dossier-title"]  *=  ${part_of}
  Get Text  //*[@data-e2e-name="dossier-period"]  *=  ${period}
  Get Text  //*[@data-e2e-name="dossier-type"]  *=  ${dossier_type}
  Get Text  //*[@data-e2e-name="dossier-date"]  *=  ${dossier_date}
  Get Text  //*[@data-e2e-name="dossier-publication-size"]  *=  ${publication_size}

Verify Related Document Mentions
  [Arguments]  ${related_document}
  Get Text  //body  *=  ${related_document}
