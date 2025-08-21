*** Settings ***
Documentation       Tests that verify the Sphinx documentation generation.
Library             DebugLibrary
Resource            ../resources/Organisations.resource
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Suite Setup         Suite Setup
Test Tags           ci  documentation  parallel


*** Test Cases ***
Validate Documentation
  Click Documentation Link In Footer
  Get Text  //section//h1  contains  Welkom bij de documentatie van het publicatieplatform
  Verify Index Item  Publiceren
  Verify Image  (//section//img)[1]

Validate Link In WooDecision Upload Step
  Go To Admin
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Click  //*[@data-e2e-name="doc-link"]
  Switch Page  NEW
  Get Text  //section//h1  contains  Productierapport uitgelegd
  Get Element States  //a[contains(@href, 'productierapport_template.xlsx')]  contains  attached


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Click Documentation Link In Footer
  Click  //footer//a[contains(.,'Documentatie')]

Verify Index Item
  [Arguments]  ${item_text}
  Click  //div[contains(@class, 'toctree-wrapper')]//a[contains(.,'${item_text}')]
  Get Text  //div[contains(@class,'div-main-content')]//h1  contains  ${item_text}
