*** Settings ***
Documentation       Admin UI tests for NoticeNotPublic (Mededeling niet-openbaar).
...                 Tests the full UI flow: add, edit, delete, and business rules.
Resource            ../../resources/Advice.resource
Resource            ../../resources/NoticeNotPublic.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Setup          Go To Admin
Test Tags           ci  notice-not-public


*** Variables ***
${DOSSIER_REFERENCE}    ${EMPTY}


*** Test Cases ***
Add Notice Not Public Via UI Shows Success Alert
  [Documentation]    Adding a notice via the UI dialog shows a success alert
  ...    containing "Mededeling is toegevoegd."
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Admin Notice Alert Should Contain  Mededeling is toegevoegd.

Edit Notice Not Public Via UI Updates The Notice
  [Documentation]    Editing an existing notice via the UI dialog shows
  ...    "Mededeling is bijgewerkt." and the new date is visible in the card.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Admin Notice Alert Should Contain  Mededeling is toegevoegd.
  Edit Notice Not Public Via Admin UI  formal_date=15062023
  Admin Notice Alert Should Contain  Mededeling is bijgewerkt.
  Get Text  //div[@data-e2e-name="notice-not-public"]  contains  15 juni 2023

Delete Notice Not Public Via UI Shows Success Alert
  [Documentation]    Deleting a notice via the Verwijderen button in the card
  ...    shows "Mededeling is verwijderd." and the add-notice button reappears.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Reload
  Delete Notice Not Public Via Admin UI
  Admin Notice Alert Should Contain  Mededeling is verwijderd.
  Wait For Elements State  //*[@data-e2e-name="add-notice"]  visible

Add Notice Button Is Disabled When Main Document Exists
  [Documentation]    The "Mededeling toevoegen..." button is disabled (and not
  ...    clickable) when the dossier already has a main document.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Upload Advice  ${FILE_LOCATION}
  Get Element States  //*[@data-e2e-name="add-notice"]  contains  disabled

Publish Advice With Notice And Without Main Document Succeeds
  [Documentation]    An advice with a NoticeNotPublic and no main document
  ...    can be successfully published (satisfies RequiresMainDocumentOrNoticeNotPublic).
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Verify Publication Badge Status  Openbaar

Notice Not Public Section Visible In Admin Dossier View
  [Documentation]    After adding a notice and viewing the dossier in the admin
  ...    view, the notice section is rendered with formalDate, grounds and documentName.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Add Notice Not Public Via Admin UI
  ...  formal_date=01012022
  ...  grounds_value=5.1.1a
  ...  document_name=Testdocument
  ...  explanation=Toelichting voor test
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Get Element States  //div[@data-e2e-name="notice-not-public-section"]  contains  attached
  Get Text  //div[@data-e2e-name="notice-not-public-section"]  contains  Testdocument
  Get Text  //div[@data-e2e-name="notice-not-public-section"]  contains  1 weigeringsgrond en een toelichting

Publishing Without Main Document Or Notice Fails Validation
  [Documentation]    Attempting to publish an advice dossier without either a main
  ...    document or a notice shows a validation error.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Click Save And Continue
  Get Text
  ...  //div[@data-e2e-name="input-errors-noticeNotPublic"]
  ...  contains
  ...  Een dossier moet ten minste een hoofddocument of een mededeling niet-openbaar bevatten

History Entry Created On Notice Add
  [Documentation]    Adding a notice creates a history entry with the expected message.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Get Text  //*[@data-e2e-name="history"]  contains  Mededeling 'niet-openbaar' toegevoegd

History Entry Created On Notice Delete
  [Documentation]    Deleting a notice creates a history entry with the expected message.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Click Save And Continue
  Go Back
  Delete Notice Not Public Via Admin UI
  Reload
  Fill Out Advice Details  has_attachment=${False}
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Get Text  //*[@data-e2e-name="history"]  contains  Mededeling 'niet-openbaar' verwijderd

History Entry Created On Notice Edit
  [Documentation]    Editing a notice creates a history entry with the expected message.
  Click Publications
  Create New Dossier  advice
  Generate Test Data Set  advice
  Fill Out Basic Details  type=advice
  Fill Out Summary
  Add Notice Not Public Via Admin UI  formal_date=01012022  grounds_value=5.1.1a
  Edit Notice Not Public Via Admin UI  formal_date=15062023
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  Search For A Publication  ${DOSSIER_REFERENCE}
  Get Text  //*[@data-e2e-name="history"]  contains  Mededeling 'niet-openbaar' gewijzigd


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
