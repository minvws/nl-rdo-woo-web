# Woo-platform Ministry of VWS

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=nl-rdo-woo-web-private&metric=alert_status&token=b35ec24b06834af668d51efc85b6f181dabf4a5b)](https://sonarcloud.io/summary/new_code?id=nl-rdo-woo-web-private) [![E2E Tests - CI](https://github.com/minvws/nl-rdo-woo-web-private/actions/workflows/e2e-ci.yml/badge.svg)](https://github.com/minvws/nl-rdo-woo-web-private/actions/workflows/e2e-ci.yml)

## Introduction

This repository contains the source code and the technical documentation of the website <https://open.minvws.nl/>.

**Design, context and contribution information of the project OpenMinVWS can be found [here](https://github.com/minvws/nl-rdo-woo-coordination)).**

Note: The published version does not include the Rijksoverheid theme used on OpenMinVWS. The site look and feel is very minimal out of the box.

## Technical information

For technical info, see the [Technical](docs/technical.md) documentation.

## Installation

The Woo platform is based on the Symfony framework and uses Elasticsearch as search engine.

- For installing, see the [Install](docs/install.md) documentation
- For updating or local setup troubleshooting, see the [Update](docs/update.md) documentation
- For Elasticsearch, see the [Elasticsearch](docs/elastic_index.md) documentation.
- For platform console commands, see the [Commands](docs/commands.md) documentation.

## Roles and permissions

For Roles and permissions see the [Access roles](docs/access-roles.md) documentation.

## Doctrine entities

For Doctrine entities, see the [Doctrine](docs/doctrine.md) documentation.

## Terminology

For terminology, see the [Terminology](docs/terminology.md) documentation.

## Licensing

The source code of this Woo-platform is released under the [EUPL license](./LICENSES/EUPL-1.2.txt).
The documentation is released under the [CC0 license](./LICENSES/CC0-1.0.txt).
The EUPL 1.2 and the CC0 do not apply to photos, videos, infographics, fonts or other forms of media.
Specifically the rijkslogo and rijkshuisstijl have specific [terms of use](./LICENSES/LicenseRef-Proprietary.txt).

Please see the [.reuse/dep5](./.reuse/dep5) file for more details, which follows the [Reuse specfication](https://reuse.software/spec/).
