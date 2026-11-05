*** Comments ***
# robocop: off=no-test-variable


*** Settings ***
Documentation       Tests that the main document and attachment of published dossiers can be found on the public search,
...                 by their content. Every file gets its own unique word, so each search should find only that file.
...                 The file names don't contain these words, so a match can only come from the extracted content.
Resource            ../../resources/AnnualReport.resource
Resource            ../../resources/Organisations.resource
Resource            ../../resources/OtherPublication.resource
Resource            ../../resources/Setup.resource
Resource            ../../resources/TestData.resource
Suite Setup         Suite Setup
Suite Teardown      Suite Teardown
Test Teardown       Run Keyword If Test Failed  Take Screenshot
Test Tags           ci  public  search-content-dossiers


*** Test Cases ***
Content Of Annual Report Documents Is Found
  ${main_name}  ${main_word}  ${attachment_name}  ${attachment_word} =  Publish Dossier With Documents  annual-report
  Search Should Find Only Document In Dossier  ${main_word}  ${main_name}
  Search Should Find Only Document In Dossier  ${attachment_word}  ${attachment_name}

Content Of Other Publication Documents Is Found After Another Dossier Was Ingested
  [Documentation]  Uploaded files once shared cached content when they had no file hash, so a file got the content
  ...  of a file ingested earlier. A second dossier with different files verifies each file gets its own content.
  ${main_name}  ${main_word}  ${attachment_name}  ${attachment_word} =  Publish Dossier With Documents
  ...  other-publication
  Search Should Find Only Document In Dossier  ${main_word}  ${main_name}
  Search Should Find Only Document In Dossier  ${attachment_word}  ${attachment_name}


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Login Admin
  Select Organisation

Suite Teardown
  No-Click Logout
  Close Browser
  Clear TestData Folder

Publish Dossier With Documents
  [Arguments]  ${type}
  ${location} =  Create Unique TestData Location
  ${main_location}  ${main_word} =  Create TXT Document With Unique Word  ${location}
  ${attachment_location}  ${attachment_word} =  Create TXT Document With Unique Word  ${location}
  ${_}  ${main_name} =  Split Path  ${main_location}
  ${_}  ${attachment_name} =  Split Path  ${attachment_location}
  VAR  ${ATTACHMENT_LOCATION} =  ${attachment_location}  scope=test
  VAR  ${ATTACHMENT_FILENAME} =  ${attachment_name}  scope=test
  Go To Admin
  Click Publications
  Create New Dossier  ${type}
  Fill Out Basic Details  type=${type}
  Fill Out Summary
  IF  "${type}" == "annual-report"
    Upload Annual Report  ${main_location}
  ELSE IF  "${type}" == "other-publication"
    Upload Informatiestuk  ${main_location}
  END
  Upload Attachment  brief
  Click Save And Continue
  Publish Dossier And Return To Admin Home
  RETURN  ${main_name}  ${main_word}  ${attachment_name}  ${attachment_word}

Create TXT Document With Unique Word
  [Arguments]  ${location}
  ${file_id} =  Generate Random String  12  [LOWER]
  ${word} =  Generate Random String  16  [LOWER]
  ${body} =  Generate Body Text
  VAR  ${file_location} =  ${location}/${file_id}.txt
  Create File  ${file_location}  ${word} ${body}
  RETURN  ${file_location}  ${word}

Search Should Find Only Document In Dossier
  [Documentation]  Retries until the document is indexed, instead of waiting for all queues to empty, as parallel
  ...  suites keep the queues busy.
  [Arguments]  ${search_term}  ${file_name}
  Wait Until Keyword Succeeds  1min  5s  Public Search Should Find Only Document  ${search_term}  ${file_name}

Public Search Should Find Only Document
  [Arguments]  ${search_term}  ${file_name}
  Go To Public
  Initiate Search  ${search_term}
  Verify Search Result Count  1 resultaat
  Get Text  //li[@data-e2e-name="search-result"]  contains  ${file_name}
  Get Text  //li[@data-e2e-name="search-result"]  contains  ${DOSSIER_REFERENCE}
