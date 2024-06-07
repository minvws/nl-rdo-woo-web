*** Settings ***
Documentation       Resource file containing custom keywords for inquiries in the admin console.
Library             Browser
Library             DebugLibrary
Resource            Generic.resource


*** Keywords ***
Verify Existence Of Inquiry
  [Arguments]  ${inquiry_number}
  Get Text  css=#inhoud > div > div.bhr-card  contains  ${inquiry_number}

Verify Inquiry Summary
  [Arguments]  ${decision_name}  ${document_summary_string}
  Get Text  //body  *=  ${decision_name}
  Get Text  //body  *=  ${document_summary_string}

Verify Documents Summary In Inquiry Overview
  [Arguments]  ${document_summary_string}
  Get Text  //body  *=  ${document_summary_string}

Verify Document Listed In Inquiry Overview
  [Arguments]  ${document_name}
  Get Text  //body  *=  ${document_name}

Verify Decision Listed In Inquiry Overview
  [Arguments]  ${decision_name}
  Get Text  //body  *=  ${decision_name}
