*** Settings ***
Documentation       Tests that verify the Sphinx documentation generation.
Library             DebugLibrary
Resource            ../resources/Setup.resource
Resource            ../resources/WooDecision.resource
Resource            ../resources/Organisations.resource
Suite Setup         Suite Setup
Test Tags           ci  documentation


*** Test Cases ***
Validate Documentation
  Click Documentation Link In Footer
  Get Text  //article//h1  contains  Welkom bij de documentatie van het publicatieplatform
  Verify Index Item  Publiceren
  Verify Image  (//article//img)[1]  100

Validate Link In WooDecision Upload Step
  [Tags]  deze
  Go To Admin
  Create New Dossier  woo-decision
  Fill Out Basic Details
  Fill Out WooDecision Details  Openbaarmaking
  Click  //*[@data-e2e-name="doc-link"]
  Switch Page  NEW
  Get Text  //article//h1  contains  Productierapport uitgelegd
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
  Get Text  //article//h1  contains  ${item_text}

Verify Image
  [Arguments]  ${selector}  ${expected_min_height}
  ${bounding_box} =  Get BoundingBox  ${selector}
  Should Be True  ${bounding_box.height} > ${expected_min_height}
