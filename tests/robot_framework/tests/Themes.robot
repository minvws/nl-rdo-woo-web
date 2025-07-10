*** Settings ***
Documentation       If we remove the Covid19 page (https://github.com/minvws/nl-rdo-woo-web-private/issues/4742), these tests should be removed as well.
Resource            ../resources/Dossier.resource
Resource            ../resources/Setup.resource
Suite Setup         Suite Setup Generic
Test Setup          Go To Public
Test Tags           ci  themes


*** Test Cases ***
Covid19 Theme Page Accessible
  Navigate To Covid19 Theme Page
  Get Text  //main[@data-e2e-name="main-content"]  contains  Alle COVID-19 gerelateerde besluiten

Covid19 Theme Page Only Shows Covid19 Dossiers
  [Documentation]  Since none of the tests use the Covid19 subjects, the result should always be 0.
  Navigate To Covid19 Theme Page
  Compare Search Result Summary  0  0
