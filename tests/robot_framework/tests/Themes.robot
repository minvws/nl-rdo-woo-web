*** Settings ***
Documentation       Skipped because it fails due to the issue mentioned here: https://github.com/minvws/nl-rdo-woo-web-private/blob/273f83b6160bc29d73ac95a5f2d1523cff65cbd3/tests/robot_framework/resources/Setup.resource#L148
...                 If we remove the Covid19 page (https://github.com/minvws/nl-rdo-woo-web-private/issues/4742), these tests should be removed as well.
Resource            ../resources/Setup.resource
Resource            ../resources/Dossier.resource
Suite Setup         Suite Setup Generic
Test Setup          Go To Public
Test Tags           ci  themes

*** Test Cases ***
Covid19 Theme Page Accessible
  Skip
  Load VWS Fixtures
  Navigate To Covid19 Theme Page
  Get Text  //main[@data-e2e-name="main-content"]  contains  Alle COVID-19 gerelateerde besluiten

Covid19 Theme Page Only Shows Covid19 Dossiers
  [Documentation]  Since none of the tests use the Covid19 subjects, the result should always be 0.
  Skip
  Navigate To Covid19 Theme Page
  Compare Search Result Summary  0  0
