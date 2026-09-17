*** Comments ***
# robotcode: ignore


*** Settings ***
Documentation       Verifies that the inquiry preview link exposed in the WooDecision HAL links
...                 only grants access to a dossier during its actual preview window: not accessible
...                 while scheduled (before previewDate), accessible during preview only after
...                 visiting the inquiry link (session-gated), and unconditionally accessible once
...                 published.
Resource            ../../resources/WooDecisionAPI.resource
Suite Setup         Suite Setup API
Test Tags           api  api-inquiries


*** Test Cases ***
Scheduled Dossier Is Not Listed On Its Inquiry Page
  Create WooDecision Dossier In Status  scheduled
  ${dossier} =  Get WooDecision
  Create Public Session  alias=public
  ${response} =  GET Href On Session  public  ${dossier}[_links][inquiries][0][href]
  Should Be Equal As Integers  ${response.status_code}  200
  Should Not Contain  ${response.text}  ${PREVIOUS_REQUEST_BODY}[title]

Preview Dossier Is Only Accessible After Visiting Its Inquiry Link
  Create WooDecision Dossier In Status  preview
  ${dossier} =  Get WooDecision
  VAR  ${inquiry_href} =  ${dossier}[_links][inquiries][0][href]
  ${dossier_path} =  Scrape Dossier Path From Inquiry Page  ${inquiry_href}
  # A session that never visited the inquiry link must be denied.
  Create Public Session  alias=preview_session
  ${denied} =  GET On Session  preview_session  ${dossier_path}  expected_status=any
  Should Be Equal As Integers  ${denied.status_code}  404
  # Visiting the inquiry link primes the session, after which access is granted.
  GET Href On Session  preview_session  ${inquiry_href}
  ${granted} =  GET On Session  preview_session  ${dossier_path}  expected_status=any
  Should Be Equal As Integers  ${granted.status_code}  200

Published Dossier Is Accessible Without Ever Visiting Its Inquiry Link
  Create WooDecision Dossier In Status  published
  ${dossier} =  Get WooDecision
  Create Public Session  alias=public_session
  ${response} =  GET Href On Session  public_session  ${dossier}[_links][public][href]
  Should Be Equal As Integers  ${response.status_code}  200


*** Keywords ***
Get WooDecision
  ${response} =  GET On Session
  ...  alias=publication_api
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision/external/${EXTERNAL_ID}
  RETURN  ${response.json()}

GET Href On Session
  [Documentation]    Strips the scheme and host off an absolute HAL href before requesting it on
  ...    a RequestsLibrary session, since sessions are bound to a single base host.
  [Arguments]  ${alias}  ${href}
  ${path} =  Remove String Using Regexp  ${href}  ^https?://[^/]+
  ${response} =  GET On Session  alias=${alias}  url=${path}  expected_status=any
  RETURN  ${response}

Scrape Dossier Path From Inquiry Page
  [Documentation]    The API never exposes the case-scoped dossier path (/zaak/.../dossier/...)
  ...    directly, so this visits the inquiry landing page with a throwaway session and extracts
  ...    it from the dossier link in the rendered HTML.
  [Arguments]  ${inquiry_href}
  Create Public Session  alias=scraper
  ${response} =  GET Href On Session  scraper  ${inquiry_href}
  Should Contain  ${response.text}  ${PREVIOUS_REQUEST_BODY}[title]
  ${matches} =  Get Regexp Matches
  ...  ${response.text}
  ...  href="(/zaak/[^"]+/dossier/[^"]+)">${PREVIOUS_REQUEST_BODY}[title]
  ...  1
  Should Not Be Empty  ${matches}  msg=Could not find the dossier link on its inquiry page
  RETURN  ${matches}[0]
