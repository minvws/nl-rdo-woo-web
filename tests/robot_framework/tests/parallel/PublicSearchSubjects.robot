*** Settings ***
Documentation       Tests that focus on finding subject landingpages through the public search, on their title
...                 and description as well as on the nodes of their content tree.
Resource            ../../resources/Setup.resource
Resource            ../../resources/Subjects.resource
Suite Setup         Suite Setup
Suite Teardown      Close Browser
Test Teardown       Run Keyword If Test Failed  Take Screenshot
Test Tags           ci  public-search-subjects


*** Test Cases ***
Subject Is Found By Its Landingpage Title
  ${word} =  Generate Search Word
  Login Admin
  ${_}  ${slug} =  Create Subject With Landingpage  title=Zoekbaar onderwerp ${word}
  Wait Until Search Finds Subject  ${word}  ${slug}
  # A subject is not a publication, so it doesn't count towards the "in N publicaties" counter.
  Compare Search Result Summary  1  0
  Subject Search Result Should Be Labelled Onderwerp  ${slug}
  Click  //a[@data-e2e-name="main-link" and contains(@href,'/onderwerp/${slug}')]
  Get Url  equal  ${URL_PUBLIC}/onderwerp/${slug}

Subject Is Found By Its Landingpage Description
  ${word} =  Generate Search Word
  Login Admin
  ${_}  ${slug} =  Create Subject With Landingpage  description=Een beschrijving met het woord ${word} erin.
  Wait Until Search Finds Subject  ${word}  ${slug}
  Compare Search Result Summary  1  0
  Subject Search Result Should Be Labelled Onderwerp  ${slug}

Concept Subject Landingpage Is Not Found
  ${word} =  Generate Search Word
  Login Admin
  ${_}  ${concept_slug} =  Create Subject With Landingpage  status=concept  title=Concept onderwerp ${word}
  ${_}  ${published_slug} =  Create Subject With Landingpage  title=Gepubliceerd onderwerp ${word}
  # Both are indexed by the same background job, so once the published one shows up the concept one would too.
  Wait Until Search Finds Subject  ${word}  ${published_slug}
  Compare Search Result Summary  1  0
  Subject Search Result Should Not Exist  ${concept_slug}

Type Facet Has No Subject Option
  ${word} =  Generate Search Word
  Login Admin
  ${_}  ${slug} =  Create Subject With Landingpage  title=Onderwerp zonder filteroptie ${word}
  Wait Until Search Finds Subject  ${word}  ${slug}
  Get Element Count  //*[@id="filters-group-type"]//input[@value="subject"]  equal  0

Subject Drops Out When Filtering On Department Or Date
  ${word} =  Generate Search Word
  Login Admin
  ${_}  ${slug} =  Create Subject With Landingpage  title=Gefilterd onderwerp ${word}
  Wait Until Search Finds Subject  ${word}  ${slug}
  ${search_url} =  Get Url
  # Subjects have neither a department nor a date, so any department or date filter excludes them.
  Go To  ${search_url}&dep%5B%5D=E2E%20Bestuursorgaan
  Compare Search Result Summary  0  0
  Go To  ${search_url}
  Compare Search Result Summary  1  0
  ${today} =  Convert Date  date=${CURRENT_DATE}  result_format=%d-%m-%Y
  Apply Date Filters Through URL  01-01-2001  ${today}
  Compare Search Result Summary  0  0

Subject Is Found By A Content Tree Node Title
  ${word} =  Generate Search Word
  Login Admin
  ${name}  ${slug} =  Create Subject With Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Onderdeel ${word}  Beschrijving van het onderdeel.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Wait Until Search Finds Subject  ${word}  ${slug}
  Compare Search Result Summary  1  0
  Subject Search Result Should Be Labelled Onderwerp  ${slug}
  Subject Search Result Should Highlight  ${slug}  ${word}

Subject Is Found By The Body Of A Nested Content Tree Node
  ${word} =  Generate Search Word
  Login Admin
  ${name}  ${slug} =  Create Subject With Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Niveau 1  Beschrijving van niveau 1.
  Add Content Tree Node  1
  Fill Content Tree Node  1.1  Niveau 2  Beschrijving van niveau 2.
  Add Content Tree Node  1.1
  Fill Content Tree Node  1.1.1  Niveau 3  Een diep genest onderdeel over ${word}.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Wait Until Search Finds Subject  ${word}  ${slug}
  Compare Search Result Summary  1  0
  Subject Search Result Should Highlight  ${slug}  ${word}

Hidden Content Tree Does Not Match But Subject Title Still Does
  ${title_word} =  Generate Search Word
  ${node_word} =  Generate Search Word
  Login Admin
  ${name}  ${slug} =  Create Subject With Landingpage  title=Onderwerp met verborgen verhaallijn ${title_word}
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Onderdeel ${node_word}  Beschrijving van het onderdeel.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Wait Until Search Finds Subject  ${node_word}  ${slug}
  Login Admin
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Set Subject Landingpage Content Tree Status  concept
  Click Submit Subject Landingpage
  Wait Until Keyword Succeeds  10x  3s  Search On Public And Compare Result Count  ${node_word}  0
  Wait Until Search Finds Subject  ${title_word}  ${slug}

Markdown In A Content Tree Node Body Is Not Indexed Literally
  ${word} =  Generate Search Word
  Login Admin
  ${name}  ${slug} =  Create Subject With Landingpage
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Opgemaakt onderdeel  Tekst met **${word}** en een [linktekst](https://example.org) erin.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Wait Until Search Finds Subject  ${word}  ${slug}
  Subject Search Result Should Highlight  ${slug}  ${word}
  ${result} =  Subject Search Result Locator  ${slug}
  ${summary} =  Get Text  ${result}
  Should Not Contain  ${summary}  **
  Should Not Contain  ${summary}  ](

Content Tree Body Words Get No Spelling Suggestion
  [Documentation]  Content tree text deliberately doesn't feed the "Bedoelde u misschien" suggestions, so text from
  ...              a hidden content tree can't leak through them. The subject title still does.
  ${title_word} =  Generate Search Word
  ${body_word} =  Generate Search Word
  Login Admin
  ${name}  ${slug} =  Create Subject With Landingpage  title=Onderwerp met suggestie ${title_word}
  Click Subjects
  Click Edit Subject Landingpage  ${name}
  Add Content Tree Node
  Fill Content Tree Node  1  Onderdeel zonder suggestie  Een woord zonder suggestie: ${body_word}.
  Set Subject Landingpage Content Tree Status  published
  Click Submit Subject Landingpage
  Wait Until Search Finds Subject  ${body_word}  ${slug}
  ${misspelled_title_word} =  Misspell Word  ${title_word}
  Search On Public For  ${misspelled_title_word}  0
  Get Text  //*[@id="js-search-results"]  contains  Bedoelde u misschien
  ${misspelled_body_word} =  Misspell Word  ${body_word}
  Search On Public For  ${misspelled_body_word}  0
  Get Text  //*[@id="js-search-results"]  not contains  Bedoelde u misschien


*** Keywords ***
Suite Setup
  Suite Setup Generic

Subject Search Result Locator
  [Arguments]  ${slug}
  RETURN  //li[@data-e2e-name="search-result"][.//a[@data-e2e-name="main-link" and contains(@href,'/onderwerp/${slug}')]]

Generate Search Word
  [Documentation]  A random, lowercase-only word, so a search for it only matches the subject created in that test.
  ${word} =  Generate Random String  12  [LOWER]
  RETURN  ${word}

Misspell Word
  [Arguments]  ${word}
  VAR  ${replacement} =  q
  IF  '${word}[6]' == 'q'  VAR  ${replacement} =  z
  RETURN  ${word}[:6]${replacement}${word}[7:]

Search On Public And Compare Result Count
  [Arguments]  ${query}  ${expected_count}
  Go To Public
  Initiate Search  ${query}
  Compare Search Result Summary  ${expected_count}  IGNORE

Search On Public And Find Subject
  [Arguments]  ${query}  ${slug}
  Go To Public
  Initiate Search  ${query}
  ${result} =  Subject Search Result Locator  ${slug}
  Get Element Count  ${result}  equal  1

Wait Until Search Finds Subject
  [Documentation]  Subjects are indexed by a background job, so a search right after saving can still miss them.
  [Arguments]  ${query}  ${slug}
  Wait Until Keyword Succeeds  10x  3s  Search On Public And Find Subject  ${query}  ${slug}

Subject Search Result Should Be Labelled Onderwerp
  [Arguments]  ${slug}
  ${result} =  Subject Search Result Locator  ${slug}
  Get Text  ${result}//header  contains  Onderwerp

Subject Search Result Should Not Exist
  [Arguments]  ${slug}
  ${result} =  Subject Search Result Locator  ${slug}
  Get Element Count  ${result}  equal  0

Subject Search Result Should Highlight
  [Arguments]  ${slug}  ${word}
  ${result} =  Subject Search Result Locator  ${slug}
  Get Text  ${result}//p//strong  contains  ${word}
