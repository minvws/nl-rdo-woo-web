*** Comments ***
# robocop: off=no-test-variable


*** Settings ***
Documentation       Contains keywords for generating testdata.
Library             DateTime
Library             OperatingSystem
Library             String
Library             ../libraries/LogDisable.py
Library             ArchiveLibrary
Library             Browser
Library             DebugLibrary
Library             ExcelLibrary
Library             FakerLibrary
Library             RPA.PDF
Resource            Admin.resource


*** Variables ***
${TEST_DATA_ROOT}               files
${PRODUCTION_REPORT_TEMPLATE}   ${TEST_DATA_ROOT}/production_report_template.xlsx
${PDF_TEMPLATE}                 ${TEST_DATA_ROOT}/template.html


*** Keywords ***
Generate Test Data Set
  [Documentation]  Generates test data and stores their location in environment variables with test scope.
  [Arguments]  ${type}  ${has_attachment}=${FALSE}  ${case_id}=${EMPTY}
  IF  '${type}' == 'woo-decision'
    VAR  ${nr_of_documents} =  5
    ${location} =  Generate Test Documents  ${nr_of_documents}  ${has_attachment}
    Create Zip From Files In Directory  ${location}  filename=${location}/Archive.zip
    ${production_report_location} =  Create Test Production Report  ${location}  ${case_id}
    VAR  ${PRODUCTION_REPORT} =  ${production_report_location}  scope=test
    VAR  ${DOCUMENTS} =  ${location}/Archive.zip  scope=test
    VAR  ${NUMBER_OF_DOCUMENTS} =  ${nr_of_documents}  scope=test
  ELSE
    ${location} =  Generate Test Documents  1  ${has_attachment}
    ${files} =  List Files In Directory  ${location}  pattern=*.txt
    ${file_one} =  Get From List  ${files}  0
    VAR  ${FILE_LOCATION} =  ${location}/${file_one}  scope=test
  END
  IF  ${has_attachment}
    ${files} =  List Files In Directory  ${location}  pattern=VWS*.txt
    ${attachment_file} =  Get From List  ${files}  0
    VAR  ${ATTACHMENT_FILENAME} =  ${attachment_file}  scope=test
    VAR  ${ATTACHMENT_LOCATION} =  ${location}/${attachment_file}  scope=test
  END
  RETURN  ${TEST_DATA_ROOT}

Create Unique TestData Location
  ${uuid} =  FakerLibrary.Uuid 4
  Create Directory  ${TEST_DATA_ROOT}/generated/${uuid}
  RETURN  ${TEST_DATA_ROOT}/generated/${uuid}

Generate Test Documents
  [Documentation]  Generate test documnents as ######.txt files, filled with fake content, the provided number of times, and
  ...  also a VWS*.txt file as attachment when needed, to a `generated` folder in the test data root.
  [Arguments]  ${number_of_files}  ${including_attachment}=${FALSE}
  ${test_data_location} =  Create Unique TestData Location
  FOR  ${_}  IN RANGE  ${number_of_files}
    ${file_id} =  FakerLibrary.Random Int  min=100000  max=999999
    Create TXT Document  ${test_data_location}/${file_id}.txt
  END
  IF  ${including_attachment}
    ${file_name} =  FakerLibrary.Sentence  nb_words=5
    Create TXT Document  ${test_data_location}/VWS_${file_name}txt
  END
  RETURN  ${test_data_location}

Create Test Production Report
  [Documentation]  Creates a production report Excel file for the PDFs found in the given test data location.
  [Arguments]  ${test_data_location}  ${case_id}=${EMPTY}
  @{rows} =  Parse TXT Files Into Excel Rows  ${test_data_location}  ${case_id}
  VAR  ${excel_file} =  ${test_data_location}/production_report.xlsx
  Copy File  ${PRODUCTION_REPORT_TEMPLATE}  ${excel_file}
  Write Rows To Excel File  ${excel_file}  @{rows}
  RETURN  ${excel_file}

Parse TXT Files Into Excel Rows
  [Documentation]  All TXT files excluding any starting with VWS_, since those are used as attachments.
  [Arguments]  ${test_data_location}  ${case_id}
  ${files} =  List Files In Directory  ${test_data_location}  pattern=[!V][!W][!S]*.txt
  VAR  @{rows} =
  FOR  ${file}  IN  @{files}
    ${filename}  ${_} =  Split String  ${file}  .
    @{row_data} =  Create New Row Data  ${filename}  ${case_id}
    Append To List  ${rows}  ${row_data}
  END
  RETURN  @{rows}

Generate Dummy Production Report
  [Documentation]  Generates a production report Excel file, without basing it on actual files
  [Arguments]  ${nr_of_rows}
  ${test_data_location} =  Create Unique TestData Location
  VAR  ${excel_file} =  ${test_data_location}/production_report.xlsx
  VAR  @{rows} =
  Copy File  ${PRODUCTION_REPORT_TEMPLATE}  ${excel_file}
  Open Excel Document  ${excel_file}  prodrep
  FOR  ${_}  IN RANGE  ${nr_of_rows}
    ${filename} =  Generate Random Filename
    @{row_data} =  Create New Row Data  ${filename}
    Append To List  ${rows}  ${row_data}
  END
  Write Rows To Excel File  ${excel_file}  @{rows}
  RETURN  ${excel_file}

Create New Row Data
  [Arguments]  ${document_id}  ${case_id}=${EMPTY}
  # Define each column
  VAR  ${matter} =  WOO
  VAR  ${family} =  1
  VAR  ${email_thread_id} =  1
  ${document_name} =  FakerLibrary.Sentence
  ${document_name} =  Catenate  ${document_name}txt
  VAR  ${file_type} =  Word Processing
  ${date} =  FakerLibrary.Date This Year
  ${date} =  Convert Date  ${date}  result_format=%d/%m/%Y 12:00 AM UTC
  VAR  ${beoordeling} =  Openbaar
  VAR  ${opgeschort} =  ${EMPTY}
  VAR  ${beoordelingsgrond} =  ${EMPTY}
  VAR  ${toelichting} =  ${EMPTY}
  VAR  ${publieke_link} =  ${EMPTY}
  VAR  ${gerelateerd_id} =  ${EMPTY}
  VAR  ${zaaknummer} =  ${case_id}
  # Create array of fields
  VAR  @{row_data} =
  ...  ${document_id}
  ...  ${matter}
  ...  ${family}
  ...  ${email_thread_id}
  ...  ${document_name}
  ...  ${file_type}
  ...  ${date}
  ...  ${beoordeling}
  ...  ${opgeschort}
  ...  ${beoordelingsgrond}
  ...  ${toelichting}
  ...  ${publieke_link}
  ...  ${gerelateerd_id}
  ...  ${zaaknummer}
  RETURN  @{row_data}

Write Rows To Excel File
  [Arguments]  ${excel_file}  @{rows}
  Open Excel Document  ${excel_file}  prodrep
  Write Excel Rows  rows_data=@{rows}  rows_offset=1  col_offset=0
  Save Excel Document  ${excel_file}
  Close All Excel Documents

Clear TestData Folder
  ${directory_exists} =  Run Keyword And Return Status  Directory Should Exist  ${TEST_DATA_ROOT}/generated
  IF  ${directory_exists}  Empty Directory  ${TEST_DATA_ROOT}/generated

Create PDF From Template
  [Documentation]  Currently not used anymore, replaced with Create TXT Document
  [Arguments]  ${output_location}
  ${body} =  Generate Body Text
  VAR  &{data} =  body=${body}
  Template HTML To PDF
  ...  template=${PDF_TEMPLATE}
  ...  output_path=${output_location}
  ...  variables=${data}
  ${pdf_content} =  Get Text From PDF  ${output_location}
  # Verify that the PDF actually has content
  ${pdf_length} =  Get Length  ${pdf_content[1]}
  ${body_length} =  Get Length  ${body}
  Evaluate  ${body_length} > ${pdf_length}

Create TXT Document
  [Arguments]  ${output_location}
  ${body} =  Generate Body Text
  Create File  ${output_location}  ${body}

Generate Body Text
  VAR  ${nr_of_paragraphs} =  2
  VAR  ${body} =  ${EMPTY}
  FOR  ${_}  IN RANGE  1  ${nr_of_paragraphs}
    ${paragraph} =  FakerLibrary.Paragraph  nb_sentences=20
    ${body} =  Catenate  ${body}  <p>  ${paragraph}  </p>
  END
  RETURN  ${body}

Generate Random Filename
  ${file_name} =  FakerLibrary.Uuid 4
  ${file_name} =  Remove String  ${file_name}  -
  RETURN  ${file_name}
