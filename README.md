# Woo-platform

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=nl-rdo-woo-web-private&metric=alert_status&token=b35ec24b06834af668d51efc85b6f181dabf4a5b)](https://sonarcloud.io/summary/new_code?id=nl-rdo-woo-web-private) [![CI](https://github.com/minvws/nl-rdo-woo-web-private/actions/workflows/ci.yml/badge.svg)](https://github.com/minvws/nl-rdo-woo-web-private/actions/workflows/ci.yml)

## Introduction

[Woo Publication Platform](https://open.minvws.nl) is the Open Government Act (Wet Open Overheid; Woo) publication platform, developed by the the Ministry of Health, Welfare, and Sport in the Netherlands (Ministerie van Volksgezondheid, Welzijn en Sport;
MinVWS). It helps government officials publish documents and improves access for journalists and the public to search them.

The Woo consists of [17 categories](https://open.overheid.nl/documenten/fd3aaf98-ad83-4a15-a526-b0511f283bad/file) of documents that need to be made public. The platform initially supported category 14 (Woo-verzoeken, -besluiten en verstrekte informatie),
and support for additional categories has been added over time.

Note: The published version does not include the Rijksoverheid theme used on OpenVWS. The site look and feel is very minimal out of the box.

## Naming

The platform was originally conceived for use within MinVWS and was called "OpenMinVWS", later shortened to "OpenVWS". As the platform expands to multiple departments, the project has been named "Woo Publication Platform".

Repository names still use the `nl-rdo-` prefix. RDO is the former name of iRealisatie ("Realisatie Digitale Ondersteuning").

## Technical information

For technical info, see the [Technical](docs/technische-documentatie/technical.md) documentation and our collection of [Architecture decision records](docs/coordination/Architecture%20Decision%20Records/)

Our commit messages follow the [pull request decoration](docs/technische-documentatie/pull-request-decoration.md) guidelines.

## Installation (for development purposes)

The Woo-platform is based on the Symfony framework and uses Elasticsearch as its search engine.

- For installing, see the [Developer Installation](docs/technische-documentatie/development_install.md) documentation
- For updating or local setup troubleshooting, see the [Update](docs/technische-documentatie/update.md) documentation
- For Elasticsearch, see the [Elasticsearch](docs/technische-documentatie/elastic_index.md) documentation.
- For platform console commands, see the [Commands](docs/technische-documentatie/commands.md) documentation.
- For testing, see the [Test](docs/technische-documentatie/test.md) documentation

## Roles and permissions

For Roles and permissions see the [Access roles](docs/technische-documentatie/access-roles.md) documentation.

## Doctrine entities

For Doctrine entities, see the [Doctrine](docs/technische-documentatie/doctrine.md) documentation.

## Terminology

For terminology, see the [Terminology](docs/technische-documentatie/terminology.md) documentation.

Additionally, information about the metadata requirements for the Woo Publication Platform can be found in the following documents:

- [Metadata: Woo decision](docs/coordination/Metadata-requirements/category-14-woo-verzoek.md)
- [Metadata: other categories](docs/coordination/Metadata-requirements/All-other-categories.md)

## Development and contribution

Our project team promotes transparency, collaboration, and innovation through open source. We welcome technical and non-technical contributions to improve the Woo Publication Platform.

For contribution guidance, see the [MinVWS contributing guide](https://github.com/minvws/.github/blob/main/CONTRIBUTING.md).

- Project repository: [minvws/nl-rdo-woo-web](https://github.com/minvws/nl-rdo-woo-web)
- Local development: [Developer Installation](docs/technische-documentatie/development_install.md)
- Technical documentation: [Technical](docs/technische-documentatie/technical.md)
- E2E Robot test coverage: [Test](docs/technische-documentatie/test.md)

## Accessibility

Accessibility is important to the Woo Publication Platform. Its accessibility statement is available in the [Toegankelijkheidsverklaring register](https://www.toegankelijkheidsverklaring.nl/register/20338).

## Licensing

The source code of this Woo-platform is released under the [EUPL license](./LICENSES/EUPL-1.2.txt).
The documentation is released under the [CC0 license](./LICENSES/CC0-1.0.txt).
The EUPL 1.2 and the CC0 do not apply to photos, videos, infographics, fonts or other forms of media.
Specifically the rijkslogo and rijkshuisstijl have specific [terms of use](./LICENSES/LicenseRef-Proprietary.txt).

Please see the [REUSE.toml](./REUSE.toml) file for more details, which follows the [Reuse specification](https://reuse.software/spec/).
