*** Settings ***
Documentation       Resource file containing custom keywords for the Complaint Judgement information category
Library             OperatingSystem
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Resource            Dossier.resource


*** Variables ***
${FILE_LOCATION}    ${EMPTY}


*** Keywords ***
Fill Out Complaint Judgement Details
  Fill Out Summary
  Upload Complaint Judgement  ${FILE_LOCATION}
  Click Save And Continue

Upload Complaint Judgement
  [Arguments]  ${complaint_judgement_location}
  ${_}  ${file_name} =  Split Path  ${complaint_judgement_location}
  Click  " Klachtoordeel toevoegen... "
  Upload File By Selector  //dialog[@open]//input[@name="uploadUuid"]  ${complaint_judgement_location}
  Wait For Condition  Text  //div[@class="bhr-file"]  contains  ${file_name}
  Type Text  //dialog[@open]//input[@name="formalDate"]  01012022
  Click  //dialog[@open]//button[@type="submit"]
  Wait For Condition  Text  //*[@id="inhoud"]  contains  Klachtoordeel '${file_name}' is toegevoegd.
