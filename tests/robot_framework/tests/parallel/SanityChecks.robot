*** Settings ***
Documentation       A minimal suite of tests that verify proper functioning of the platform without changing data. Can be run against any env.
...                 Admin checks (tagged 'sanity-admin') require a login and are restricted to Test/Acc.
Resource            ../../resources/API.resource
Resource            ../../resources/Dossier.resource
Resource            ../../resources/Sitemap.resource
Resource            ../../resources/WooDecision.resource
Suite Setup         Suite Setup
Suite Teardown      Close Browser
Test Setup          Go To Public
Test Tags           sanity


*** Test Cases ***
Homepage And Main Menu Pages Load Without Errors
  [Documentation]  Visits the homepage and every page linked from the public main menu.
  Wait For Load State  networkidle
  ${links} =  Get Public Main Nav Links
  FOR  ${href}  IN  @{links}
    Go To  ${URL_PUBLIC}${href}
    Wait For Load State  networkidle
    Page Should Load Without Error
  END

All Categories Page Shows Non-zero Counts
  Go To  ${URL_PUBLIC}/alle-categorieën
  Page Should Load Without Error
  Category Counts Should Be Shown  subject
  Category Counts Should Be Shown  type

Search With A Query And Filters Returns Results
  [Documentation]  Deliberately uses a query that is expected to return results on all environments, and a filter that is expected to return results on all environments.
  Go To  ${URL_PUBLIC}/zoeken?q=${EMPTY}
  Page Should Load Without Error
  Select Filter Options - Dossier  woo-decision
  ${results} =  Get Search Result Count
  Should Be True  ${results} > 0

Open A Woo Decision Dossier And Download Its Files
  [Documentation]  Finds any existing published Woo-besluit and downloads the main document, the inventory
  ...  list, and a document, since there is no dedicated fixture dossier on Test, Acc or Prod.
  Go To  ${URL_PUBLIC}/zoeken?q=${EMPTY}
  Select Filter Options - Dossier  woo-decision  documents=${FALSE}  attachments=${FALSE}  main_document=${FALSE}
  Click First Search Result With Documents
  Page Should Load Without Error
  Download WooDecision Inventory
  Click  //a[@data-e2e-name="main-document-detail-link"]
  Generic Download Click  //a[@data-e2e-name="download-file-link"]
  Go Back
  Click  (//div[@data-e2e-name="documents-section"]//tbody/tr//td//a)[1]
  Generic Download Click  //a[@data-e2e-name="download-file-link"]

Robots Txt Sitemap Is Available And Filled
  ${sitemap_index} =  Get WooIndex Sitemap Index From Robots  ${URL_PUBLIC}/robots.txt
  ${sitemap} =  Get First Sitemap From Sitemap Index  ${sitemap_index}
  Sitemap Should Contain Multiple URLs  ${sitemap}  0

Admin Main Menu Pages Load Without Errors
  [Tags]  sanity-admin
  Login Admin
  Wait For Load State  networkidle
  ${links} =  Get Admin Main Nav Links
  FOR  ${href}  IN  @{links}
    Go To  ${URL_ADMIN}${href}
    Wait For Load State  networkidle
    Page Should Load Without Error
  END

Admin Can Switch Organisation
  [Tags]  sanity-admin
  Login Admin
  ${switcher_count} =  Browser.Get Element Count  //*[@data-e2e-name="organisation-switcher"]
  IF  ${switcher_count} > 0
    Click Organisation Selector
    Click  (//*[@data-e2e-name="organisation-switcher"]//li)[1]
  END

Admin Can Search Publications And Open A Dossier
  [Tags]  sanity-admin
  Login Admin
  Click Publications
  ${reference} =  Get Text  (//table[@data-e2e-name="dossiers-table"]/tbody/tr[1]//span)[1]
  ${dossier_number} =  Fetch From Right  ${reference}  /
  ${dossier_number} =  Strip String  ${dossier_number}
  Search For A Publication  ${dossier_number}
  Page Should Load Without Error

Admin Stats Page Shows Numeric Queue Values
  [Documentation]  Only checks that the queue numbers on the /stats page are present, since whether they should
  ...  be rising or falling depends on whether a rollover is in progress at the time of running this check.
  [Tags]  sanity-admin
  Login Admin
  Go To  ${URL_ADMIN}/stats
  Page Should Load Without Error
  ${value} =  Get Text  (//*[@data-e2e-name="rabbitmq-stats"]//tbody/tr[1]/td[3])[1]
  Should Match Regexp  ${value}  \\d+

Publication API Woo Decisions Are Listable
  [Documentation]  Lists woo-decisions for the organisation configured in the mTLS context. Skipped on prod.
  Skip If  '${ENVIRONMENT}' == 'prod'  msg=No mTLS API on prod
  Create Session
  Retrieve Organisation ID
  ${response} =  GET On Session
  ...  alias=${API_ALIAS}
  ...  url=${URL_API}/api/publication/v1/organisation/${ORGANISATION_ID}/dossiers/woo-decision
  ...  expected_status=200
  Dictionary Should Contain Key  ${response.json()}  items


*** Keywords ***
Suite Setup
  Suite Setup Generic
  Create Public HTTP Session
  Set Browser Timeout  10 sec

Get Public Main Nav Links
  ${elements} =  Browser.Get Elements  //ul[@id="main-nav-list"]//a
  VAR  @{links} =  @{EMPTY}
  FOR  ${element}  IN  @{elements}
    ${href} =  Get Attribute  ${element}  href
    Append To List  ${links}  ${href}
  END
  RETURN  ${links}

Get Admin Main Nav Links
  ${elements} =  Browser.Get Elements  //nav[@id="main-nav"]//a[starts-with(@data-e2e-name,'nav-')]
  VAR  @{links} =  @{EMPTY}
  FOR  ${element}  IN  @{elements}
    ${href} =  Get Attribute  ${element}  href
    ${href} =  Remove String Using Regexp  ${href}  ^/balie
    Append To List  ${links}  ${href}
  END
  RETURN  ${links}

Page Should Load Without Error
  ${error_count} =  Browser.Get Element Count  //h2[@class="exception-http"]
  IF  ${error_count} > 0  Take Screenshot
  Should Be Equal As Integers  ${error_count}  0  msg=Page shows a Symfony error page
  Get Text
  ...  //body
  ...  not contains
  ...  Pagina niet gevonden
  ...  msg=Page shows a Not Found error page

Category Counts Should Be Shown
  [Arguments]  ${facet_key}
  ${text} =  Get Text  //div[@id="${facet_key}"]
  Should Match Regexp  ${text}  \\(\\d+\\)  msg=No counts found for facet '${facet_key}'
