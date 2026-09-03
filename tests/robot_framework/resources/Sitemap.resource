*** Settings ***
Library     Process
Library     String
Library     XML
Library     RequestsLibrary


*** Variables ***
${ADMIN_CONTAINER_NAME}     minvws-admin
${ENVIRONMENT}              ${EMPTY}
${URL_PUBLIC}               ${EMPTY}
${CA_BUNDLE}                ${EMPTY}
${HTACCESS_USERNAME}        ${EMPTY}
${HTACCESS_PASSWORD}        ${EMPTY}


*** Keywords ***
Command Generate WooIndex
  VAR  ${command} =  docker exec ${ADMIN_CONTAINER_NAME} bin/console --tenant=minvws Woo-index:generate
  Run Process  ${command}  shell=True  alias=shell
  ${result} =  Get Process Result  shell
  Should Be Empty  ${result.stderr}

Create Public HTTP Session
  IF  '${ENVIRONMENT}' == 'local' or '${ENVIRONMENT}' == 'docker'
    RequestsLibrary.Create Session  alias=public  url=${URL_PUBLIC}  verify=${CA_BUNDLE}
  ELSE IF  '${ENVIRONMENT}' == 'test' or '${ENVIRONMENT}' == 'acc'
    VAR  @{auth} =  ${HTACCESS_USERNAME}  ${HTACCESS_PASSWORD}
    RequestsLibrary.Create Session  alias=public  url=${URL_PUBLIC}  verify=True  auth=${auth}
  ELSE
    RequestsLibrary.Create Session  alias=public  url=${URL_PUBLIC}  verify=True
  END

Get WooIndex Sitemap Index From Robots
  [Arguments]  ${robots_url}
  ${response} =  GET On Session  public  ${robots_url}
  Should Contain  ${response.text}  sitemap-index.xml  msg=robots.txt does not contain a WooIndex 'sitemap-index.xml'
  ${sitemap} =  Get Lines Containing String  ${response.text}  sitemap-index.xml
  ${url} =  Remove String  ${sitemap}  Sitemap:
  ${url} =  Strip String  ${url}
  RETURN  ${url}

Get First Sitemap From Sitemap Index
  [Arguments]  ${sitemap_index_url}
  ${response} =  GET On Session  public  ${sitemap_index_url}
  ${root} =  Parse XML  ${response.text}
  ${sitemaps} =  XML.Get Elements  ${root}  sitemap
  ${sitemap_url} =  XML.Get Element Text  ${sitemaps}[0]  loc
  RETURN  ${sitemap_url}

Sitemap Should Contain Multiple URLs
  [Arguments]  ${sitemap_url}  ${minimum_count}
  ${response} =  GET On Session  public  ${sitemap_url}
  ${root} =  Parse XML  ${response.text}
  ${count} =  XML.Get Element Count  ${root}  url
  Should Be True  ${count} > ${minimum_count}
