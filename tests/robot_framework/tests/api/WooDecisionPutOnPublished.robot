*** Comments ***
# robotcode: ignore


*** Settings ***
Documentation       PUT-update tests against an already published WooDecision dossier.
...                 Verifies which changes are accepted (dossier metadata, adding/updating
...                 documents/attachments, existing document/main document metadata) and which are
...                 rejected (removing documents or attachments, invalid field values) once a
...                 dossier is no longer in concept status.
...                 See DossierDocumentValidator::assertDocumentSetUnchangedInNonConcept and
...                 DossierAttachmentValidator::assertAttachmentSetUnchangedInNonConcept.
Resource            ../../resources/WooDecisionAPI.resource
Suite Setup         Suite Setup API
Test Tags           api  api-woodecision  api-woodecision-put-published


*** Test Cases ***
Metadata Update On Published Dossier Stays Published
  [Documentation]  title and summary have no Immutable(groups: [PUBLICATION_LOCKED]) constraint, so they
  ...  stay editable after publish, unlike decision/previewDate/publicationDate/dossierNumber.
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}  title  Updated title after publish
  Set To Dictionary  ${body}  summary  Updated summary after publish
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[title]  Updated title after publish
  Should Be Equal  ${dossier}[summary]  Updated summary after publish

Adding A Document To A Published Dossier Is Allowed
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  ${extra_document} =  Build Additional WooDecision Document
  ${documents} =  Copy List  ${body}[documents]
  Append To List  ${documents}  ${extra_document}
  Set To Dictionary  ${body}  documents  ${documents}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Length Should Be  ${dossier}[documents]  2

Removing A Document From A Published Dossier Is Rejected
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}  documents  ${{ [] }}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  422
  Publication Status Should Be  woo-decision  published

Adding An Attachment To A Published Dossier Is Allowed
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  ${attachment} =  Generate Attachment  ${{ ['c_c2f56984'] }}
  Set To Dictionary  ${body}  attachments  ${{ [$attachment] }}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Length Should Be  ${dossier}[attachments]  1

Removing An Attachment From A Published Dossier Is Rejected
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  ${attachment} =  Generate Attachment  ${{ ['c_c2f56984'] }}
  Set To Dictionary  ${body}  attachments  ${{ [$attachment] }}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  ${body} =  Copy Dictionary  ${body}  deep=True
  Set To Dictionary  ${body}  attachments  ${{ [] }}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  422
  Publication Status Should Be  woo-decision  published

Changing Attachment Metadata On A Published Dossier Does Not Force Reupload
  Create WooDecision Dossier In Status  published  with_attachment=${TRUE}
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}[attachments][0]  formalDate  2020-06-02
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[attachments][0][uploadStatus]  processed
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Attachment File Should Be Downloadable  ${EXTERNAL_ID}  ${body}[attachments][0][externalId]

Changing Decision On A Published Dossier Is Rejected
  [Documentation]  decision is Immutable(groups: [PUBLICATION_LOCKED]), so it can no longer be
  ...  changed once the dossier has left concept status.
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}  decision  not_public
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  422
  Publication Status Should Be  woo-decision  published

Changing Document Metadata On A Published Dossier Does Not Force Reupload
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}[documents][0]  remark  Updated remark after publish
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[documents][0][uploadStatus]  processed
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document File Should Be Downloadable  ${EXTERNAL_ID}  ${body}[documents][0][externalId]

Changing Document Judgement To Not Public On A Published Dossier Removes The File
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}[documents][0]  judgement  not_public
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[documents][0][uploadStatus]  no_upload_required
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document File Should Not Be Downloadable  ${EXTERNAL_ID}  ${body}[documents][0][externalId]

Suspending A Document On A Published Dossier Removes The File
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  Set To Dictionary  ${body}[documents][0]  isSuspended  ${TRUE}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[documents][0][uploadStatus]  no_upload_required
  Wait Until Keyword Succeeds  5x  2s
  ...  WooDecision Document File Should Not Be Downloadable  ${EXTERNAL_ID}  ${body}[documents][0][externalId]

Changing Main Document Metadata On A Published Dossier Does Not Force Reupload
  [Documentation]  Unlike Document, WooDecisionMainDocumentRequestMapper does not hash-compare
  ...  or clear the uploaded flag, so metadata-only changes keep the main document processed.
  Create WooDecision Dossier In Status  published
  ${body} =  Copy Dictionary  ${PREVIOUS_REQUEST_BODY}  deep=True
  ${new_grounds} =  Get Random Grounds
  Set To Dictionary  ${body}[mainDocument]  grounds  ${new_grounds}
  Send Put Request WooDecision  ${EXTERNAL_ID}  ${body}  200
  Publication Status Should Be  woo-decision  published
  ${dossier} =  Get WooDecision
  Should Be Equal  ${dossier}[mainDocument][uploadStatus]  processed


*** Keywords ***
Get WooDecision
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  RETURN  ${response.json()}

Build Additional WooDecision Document
  ${document_id} =  FakerLibrary.Random Int  min=100000  max=999999
  ${document_id} =  Convert To String  ${document_id}
  ${document_external_id} =  FakerLibrary.Uuid 4
  ${grounds} =  Get Random Grounds
  VAR  &{document} =
  ...  externalId=${document_external_id}
  ...  inquiryNumbers=${{ [] }}
  ...  documentDate=2020-06-01
  ...  documentId=${document_id}
  ...  familyId=${333}
  ...  fileName=extra-document.doc
  ...  grounds=${grounds}
  ...  isSuspended=${FALSE}
  ...  judgement=public
  ...  links=${{ [] }}
  ...  publicationContext=2025-01
  ...  refersTo=${{ [] }}
  ...  remark=Extra document added after publication
  ...  sourceType=doc
  ...  threadId=${12345}
  RETURN  ${document}
