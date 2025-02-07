*** Settings ***
Documentation       Tests that focus on the public pages
Resource            ../resources/Setup.resource
Resource            ../resources/Public.resource
Resource            ../resources/Dossier.resource
Resource            ../resources/Generic.resource
Library             Collections
Suite Setup         Suite Setup
Test Setup          Go To Public
Test Tags           ci  themes  public-init


*** Test Cases ***
Covid19 Theme Page Accessible
  Navigate To Covid19 Theme Page
  Get Text  //main[@data-e2e-name="main-content"]  contains  Alle COVID-19 gerelateerde besluiten

Covid19 Theme Page Only Shows Covid19 Dossiers
  Navigate To Covid19 Theme Page
  Only One Subject Should Be Present
  Dossier Count Should Be Equal To Subject Count


*** Keywords ***
Suite Setup
  Suite Setup - CI

Only One Subject Should Be Present
  Get Element Count
  ...  //div[@id="filters-group-subject"]//input
  ...  equals
  ...  1
  ...  message=More then one Subject filter options found!

Dossier Count Should Be Equal To Subject Count
  ${subject_count} =  Get Text  //span[@data-e2e-name="Opstart%20Corona_count"]
  ${subject_count} =  Remove String Using Regexp  ${subject_count}  [()]
  ${woodecision_count} =  Get Text  //span[@data-e2e-name="dossier_count"]
  ${woodecision_count} =  Remove String Using Regexp  ${woodecision_count}  [()]
  Should Be Equal As Numbers  ${subject_count}  ${woodecision_count}
