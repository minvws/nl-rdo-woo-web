*** Settings ***
Documentation       Tests that verify the DiWoo sitemap generation.
Library             XML
Library             DebugLibrary
Library             RequestsLibrary
Resource            ../resources/Setup.resource
Test Tags           ci  sitemap  sitemap-init


*** Variables ***
${URL_PUBLIC}   ${EMPTY}


*** Test Cases ***
Validate DiWoo Sitemap
  Set URL Variables
  Command Generate WooIndex
  ${sitemap_index} =  Get WooIndex Sitemap Index From Robots  ${URL_PUBLIC}/robots.txt
  ${sitemap} =  Get First Sitemap From Sitemap Index  ${sitemap_index}
  Sitemap Should Contain Multiple URLs  ${sitemap}  50


*** Keywords ***
Command Generate WooIndex
  Run Process  task rf:sitemap  shell=True  alias=shell
  ${result} =  Get Process Result  shell
  Should Be Empty  ${result.stderr}

Get WooIndex Sitemap Index From Robots
  [Arguments]  ${robots_url}
  ${response} =  GET  ${robots_url}
  Should Contain  ${response.text}  sitemap-index.xml  msg=robots.txt does not contain a WooIndex 'sitemap-index.xml'
  ${sitemap} =  Get Lines Containing String  ${response.text}  sitemap-index.xml
  ${url} =  Remove String  ${sitemap}  Sitemap:
  ${url} =  Strip String  ${url}
  RETURN  ${url}

Get First Sitemap From Sitemap Index
  [Arguments]  ${sitemap_index_url}
  ${response} =  GET  ${sitemap_index_url}
  ${root} =  Parse XML  ${response.text}
  ${sitemap_url} =  XML.Get Element Text  ${root}  sitemap/loc
  RETURN  ${sitemap_url}

Sitemap Should Contain Multiple URLs
  [Arguments]  ${sitemap_url}  ${minimum_count}
  ${response} =  GET  ${sitemap_url}
  ${root} =  Parse XML  ${response.text}
  ${count} =  XML.Get Element Count  ${root}  url
  Should Be True  ${count} > ${minimum_count}
