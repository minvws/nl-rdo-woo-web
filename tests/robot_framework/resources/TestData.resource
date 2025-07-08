*** Settings ***
Documentation       Contains keywords for generating testdata.
Library             OperatingSystem
Library             String
Library             DateTime
Library             Browser
Library             DebugLibrary
Library             FakerLibrary
Library             ArchiveLibrary
Library             ExcelLibrary
Library             ../libraries/LogDisable.py
Library             RPA.PDF
Resource            Admin.resource
Resource            Dossier.resource


*** Variables ***
${TEST_DATA_ROOT}               tests/robot_framework/files
${DUMMY_PDF}                    ${TEST_DATA_ROOT}/dummy.pdf
${PRODUCTION_REPORT_TEMPLATE}   ${TEST_DATA_ROOT}/production_report_template.xlsx
${PDF_TEMPLATE}                 ${TEST_DATA_ROOT}/template.html


*** Keywords ***
Generate Test Data Set
  [Documentation]  Generates test data and stores their location in environment variables with test scope.
  [Arguments]  ${type}  ${has_attachment}=${FALSE}
  IF  '${type}' == 'woo-decision'
    ${location} =  Generate Test PDFs  5  ${has_attachment}
    ${production_report_location} =  Create Test Production Report  ${location}
    Create Zip From Files In Directory  ${location}  filename=${location}/Archive.zip  compression=bzip2
    VAR  ${PRODUCTION_REPORT} =  ${production_report_location}  scope=test
    VAR  ${DOCUMENTS} =  ${location}/Archive.zip  scope=test
    VAR  ${NUMBER_OF_DOCUMENTS} =  5  scope=test
  ELSE
    ${location} =  Generate Test PDFs  1  ${has_attachment}
    ${files} =  List Files In Directory  ${location}  pattern=*.pdf
    ${file_one} =  Get From List  ${files}  0
    VAR  ${FILE_LOCATION} =  ${location}/${file_one}  scope=test
  END
  IF  ${has_attachment}
    ${files} =  List Files In Directory  ${location}  pattern=VWS*.pdf
    ${attachment_file} =  Get From List  ${files}  0
    VAR  ${ATTACHMENT_FILENAME} =  ${attachment_file}  scope=test
    VAR  ${ATTACHMENT_LOCATION} =  ${location}/${attachment_file}  scope=test
  END
  RETURN  ${TEST_DATA_ROOT}

Generate Test PDFs
  [Documentation]  Generate test PDFs as ######.pdf files, filled with fake content, the provided number of times, and also a VWS*.pdf file as attachment when needed, to a `generated` folder in the test data root.
  [Arguments]  ${number_of_files}  ${including_attachment}=${FALSE}
  ${uuid} =  FakerLibrary.Uuid 4
  VAR  ${test_data_location} =  ${TEST_DATA_ROOT}/generated/${uuid}
  FOR  ${i}  IN RANGE  ${number_of_files}
    ${file_id} =  FakerLibrary.Random Int  min=000000  max=999999
    Create PDF From Template  ${test_data_location}/${file_id}.pdf
  END
  IF  ${including_attachment}
    ${file_name} =  FakerLibrary.Sentence  nb_words=5
    Create PDF From Template  ${test_data_location}/VWS_${file_name}pdf
  END
  RETURN  ${test_data_location}

Create Test Production Report
  [Documentation]  Creates a production report Excel file for the PDFs found in the given test data location.
  [Arguments]  ${test_data_location}
  VAR  ${excel_file} =  ${test_data_location}/production_report.xlsx
  Copy File  ${PRODUCTION_REPORT_TEMPLATE}  ${excel_file}
  Open Excel Document  ${excel_file}  prodrep
  ${pdf_files} =  List Files In Directory  ${test_data_location}  pattern=[0-9]*.pdf
  FOR  ${pdf_file}  IN  @{pdf_files}
    ${filename}  ${extension} =  Split String  ${pdf_file}  .
    Add Row To Production Report  ${filename}
  END
  Save Excel Document  ${excel_file}
  Close All Excel Documents
  RETURN  ${excel_file}

Add Row To Production Report
  [Arguments]  ${document_id}
  ${row_data} =  Create New Row Data  ${document_id}
  ${column_content} =  Read Excel Column  0
  ${nr_of_rows} =  Get Length  ${column_content}
  Write Excel Row  row_num=${nr_of_rows+1}  row_data=@{row_data}

Create New Row Data
  [Arguments]  ${document_id}
  VAR  ${matter} =  WOO
  VAR  ${family} =  1
  VAR  ${email_thread_id} =  1
  ${document_name} =  FakerLibrary.Sentence
  ${document_name} =  Catenate  ${document_name}pdf
  VAR  ${file_type} =  Word Processing
  ${date} =  FakerLibrary.Date This Year
  ${date} =  Convert Date  ${date}  result_format=%d/%m/%Y 12:00 AM UTC
  VAR  ${beoordeling} =  Openbaar
  VAR  ${opgeschort} =  ${EMPTY}
  VAR  ${beoordelingsgrond} =  ${EMPTY}
  VAR  ${toelichting} =  ${EMPTY}
  VAR  ${publieke_link} =  ${EMPTY}
  VAR  ${gerelateerd_id} =  ${EMPTY}
  VAR  ${zaaknummer} =  2025-05
  #
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

Clear TestData Folder
  Empty Directory  ${TEST_DATA_ROOT}/generated

Create PDF From Template
  [Arguments]  ${output_location}
  ${body} =  Generate Body Text
  &{data} =  Create Dictionary
  ...  body=${body}
  Template HTML To PDF
  ...  template=${PDF_TEMPLATE}
  ...  output_path=${output_location}
  ...  variables=${data}
  ${pdf_content} =  Get Text From PDF  ${output_location}
  # Verify that the PDF actually has content
  ${pdf_length} =  Get Length  ${pdf_content[1]}
  ${body_length} =  Get Length  ${body}
  Evaluate  ${body_length} > ${pdf_length}

Generate Body Text
  VAR  ${nr_of_paragraphs} =  ${6}
  VAR  ${body} =  ${EMPTY}
  FOR  ${_}  IN RANGE  1  ${nr_of_paragraphs}
    ${paragraph} =  FakerLibrary.Paragraph  nb_sentences=20
    ${body} =  Catenate  ${body}  <p>  ${paragraph}  </p>
  END
  RETURN  ${body}
