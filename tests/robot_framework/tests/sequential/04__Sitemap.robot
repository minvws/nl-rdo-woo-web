*** Settings ***
Documentation       Tests that verify the DiWoo sitemap generation.
Library             XML
Library             DebugLibrary
Library             RequestsLibrary
Resource            ../../resources/Setup.resource
Resource            ../../resources/Sitemap.resource
Test Tags           ci  sitemap  sitemap-init


*** Test Cases ***
Validate DiWoo Sitemap
  Command Generate WooIndex
  ${sitemap_index} =  Get WooIndex Sitemap Index From Robots  ${URL_PUBLIC}/robots.txt
  ${sitemap} =  Get First Sitemap From Sitemap Index  ${sitemap_index}
  Sitemap Should Contain Multiple URLs  ${sitemap}  30
