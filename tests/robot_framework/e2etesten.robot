*** Settings ***
Library    Browser

Force Tags      E2E

*** Test Cases ***
Example Test
    New Browser    chromium    headless=False
    New Page    http://localhost:8000
     ${title}=    Get Title
    Should Be Equal    ${title}    Home | Woo Platform
    [Teardown]    Close Browser    CURRENT
