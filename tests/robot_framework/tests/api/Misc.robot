*** Settings ***
Documentation       A collection of miscellaneous API tests.
Resource            ../../resources/WooDecisionAPI.resource
Suite Setup         Suite Setup API


*** Test Cases ***
Create A WooDecision
  [Documentation]  Simple and quick testcase to create a WooDecision via API
  [Tags]  api-single
  Create WooDecision Dossier In Status  published
