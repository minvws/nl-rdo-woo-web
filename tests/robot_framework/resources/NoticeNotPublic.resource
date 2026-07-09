*** Settings ***
Documentation       Keywords for NoticeNotPublic (Mededeling niet-openbaar) tests.
Library             Browser
Library             FakerLibrary
Resource            Admin.resource
Resource            Dossier.resource


*** Variables ***
${NOTICE_DIALOG}    //dialog[@data-e2e-name="notice-not-public"]


*** Keywords ***
Open Add Notice Dialog
  [Documentation]    Clicks the "add notice" button and waits for the dialog to open.
  Click  //*[@data-e2e-name="add-notice"]
  Wait For Elements State  ${NOTICE_DIALOG}  visible

Open Edit Notice Dialog
  [Documentation]    Clicks the "edit notice" button and waits for the dialog to open.
  Click  //*[@data-e2e-name="edit-notice"]
  Wait For Elements State  ${NOTICE_DIALOG}  visible

Fill Notice Form
  [Documentation]    Fills the notice form fields inside the open dialog.
  ...    formal_date should be in ddmmyyyy format (e.g. 01012022).
  ...    grounds_value should be the option value string (e.g. 5.1.1a).
  [Arguments]  ${formal_date}  ${grounds_value}  ${document_name}=${EMPTY}  ${explanation}=${EMPTY}
  IF  '${document_name}' != '${EMPTY}'
    Fill Text  ${NOTICE_DIALOG}//input[@name="documentName"]  ${document_name}
  END
  Type Text  ${NOTICE_DIALOG}//input[@name="formalDate"]  ${formal_date}
  Select Options By  ${NOTICE_DIALOG}//select[@name="grounds[0]"]  value  ${grounds_value}
  IF  '${explanation}' != '${EMPTY}'
    Fill Text  ${NOTICE_DIALOG}//textarea[@name="explanation"]  ${explanation}
  END

Add Notice Not Public Via Admin UI
  [Documentation]    Adds a new NoticeNotPublic via the admin UI dialog.
  ...    Assumes the content step page is active.
  [Arguments]  ${formal_date}=01012022  ${grounds_value}=5.1.1a  ${document_name}=${EMPTY}  ${explanation}=${EMPTY}
  Open Add Notice Dialog
  Fill Notice Form  ${formal_date}  ${grounds_value}  ${document_name}  ${explanation}
  Click  ${NOTICE_DIALOG}//button[contains(., 'Opslaan en mededeling toevoegen')]
  Wait For Elements State  //*[@data-e2e-name="alerts"]  visible

Edit Notice Not Public Via Admin UI
  [Documentation]    Edits an existing NoticeNotPublic via the admin UI dialog.
  [Arguments]  ${formal_date}=${EMPTY}  ${explanation}=${EMPTY}
  Open Edit Notice Dialog
  IF  '${formal_date}' != '${EMPTY}'
    Type Text  ${NOTICE_DIALOG}//input[@name="formalDate"]  ${formal_date}
  END
  IF  '${explanation}' != '${EMPTY}'
    Fill Text  ${NOTICE_DIALOG}//textarea[@name="explanation"]  ${explanation}
  END
  Click  ${NOTICE_DIALOG}//button[contains(., 'Opslaan en mededeling bijwerken')]
  Wait For Elements State  //*[@data-e2e-name="alerts"]  visible

Delete Notice Not Public Via Admin UI
  [Documentation]    Deletes the existing NoticeNotPublic by clicking the
  ...    Verwijderen button on the notice card (only available when canDelete is true).
  Click  //*[@data-e2e-name="delete-notice"]
  Wait For Elements State  //*[@data-e2e-name="alerts"]  visible

Admin Notice Alert Should Contain
  [Documentation]    Asserts that the success alert on the notice component contains the given text.
  [Arguments]  ${text}
  Get Text  //*[@data-e2e-name="alerts"]  contains  ${text}
