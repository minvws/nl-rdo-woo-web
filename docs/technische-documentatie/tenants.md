# Tenants

<!-- TOC -->
- [Existing tenants](#existing-tenants)
- [Before we start](#before-we-start)
- [Registering the tenant id](#registering-the-tenant-id)
- [Tenant directory layout](#tenant-directory-layout)
- [Translations](#translations)
- [Styling and assets](#styling-and-assets)
- [Tenant specific documentation](#tenant-specific-documentation)
- [Local development](#local-development)
- [Requesting environments for a new tenant](#requesting-environments-for-a-new-tenant)
- [Reference pull requests](#reference-pull-requests)
<!-- TOC -->

## Existing tenants

The `TenantId` enum in [src/TenantId.php](../../src/TenantId.php) is the source of truth for the
tenants that exist. Check the enum rather than any list in this document: it is the only place that
is guaranteed to be up to date.

## Before we start

We collect the following information before touching any code. Most of it has to come from the
organisation that is going to use the tenant, or from whoever requested the tenant.

- **The official abbreviation of the organisation.** Use the official list of ministries as the
  reference: [Rijksoverheid — ministeries](https://www.rijksoverheid.nl/ministeries) and the TOOI
  value list
  [rwc_ministeries_compleet](https://standaarden.overheid.nl/tooi/waardelijsten/expression?lijst_uri=https%3A%2F%2Fidentifier.overheid.nl%2Ftooi%2Fset%2Frwc_ministeries_compleet%2F6).
- **The tenant id.** The convention is `min<abbreviation>`, so `MINOCW` as the enum case name and
  `minocw` as its value. The `min` prefix is kept even though it is redundant for ministries: it
  leaves room to support tenants that are not ministries in the future without name conflicts.
- **The texts for the translations.** See [Translations](#translations) for the keys that have to be
  filled in. If the organisation cannot supply texts yet, we use the placeholder texts from the
  [Translations](#translations) script; the organisation can deliver its own texts later, before
  going to production.
- **Whether a custom (hero) header is needed.** This is not required to get a tenant running and can
  be picked up later as a separate ticket.

This document mainly covers adding the tenant to the application. It does not cover running the new
tenant locally, see [Local development](#local-development). Setting up the remote environments is a
separate step that comes afterwards, done through a Zammad ticket per environment, see
[Requesting environments for a new tenant](#requesting-environments-for-a-new-tenant).

## Registering the tenant id

Adding the tenant id to the [TenantId](../../src/TenantId.php) enum is the first code change. The
enum is snapshot tested by [TenantIdTest](../../tests/Unit/Domain/TenantIdTest.php), so the snapshots
in [tests/Unit/Domain/\_\_snapshots\_\_/](../../tests/Unit/Domain/__snapshots__/) have to be updated
along with it. Working through the test is the quickest way to confirm the change lands as intended.

1. **Run the test before changing anything**, to confirm we start from a green state:

   ```shell
   task composer:script:test -- --filter TenantIdTest
   ```

2. **Add the new case to the enum**, keeping the existing style — one case per tenant, uppercase case
   name, lowercase value:

   ```php
   case MINOCW = 'minocw';
   ```

   Append the new case at the end of the list, so existing tenants keep their position in
   `TenantId::cases()` and the snapshot diff stays small.

3. **Run the test again and watch it fail.** The two snapshot assertions now no longer match, which
   is exactly what we want to see: it proves the snapshots really cover the enum.

   ```shell
   task composer:script:test -- --filter TenantIdTest
   ```

4. **Update the snapshots:**

   ```shell
   task composer:script:update-test-snapshots -- --filter TenantIdTest
   ```

   Review the resulting diff. Only the new tenant should be added: to
   `TenantIdTest__testTenantId__1.yml` (name and value) and to the comma-separated list in
   `TenantIdTest__testTenantIdAsString__1.txt`. Any other change means the enum change was not
   purely additive.

5. **Run the test once more.** It should now pass:

   ```shell
   task composer:script:test -- --filter TenantIdTest
   ```

6. **Run all checks**, because more than this one test depends on the set of tenants (for example
   [TenantsJsonTest](../../tests/Unit/Domain/TenantsJsonTest.php), which fails until the tenant is
   also added to `tenants/tenants.json`, see [Styling and assets](#styling-and-assets)):

   ```shell
   task composer:script:checkall
   ```

## Tenant directory layout

Everything that is specific to a tenant lives in `tenants/<tenant_id>/`, where `<tenant_id>` is the
lowercase enum value. A minimal tenant consists of four files; use
[tenants/minbuza/](../../tenants/minbuza/) as a reference.

```text
tenants/
├── tenants.json                          # the list of tenants, see Styling and assets
└── <tenant_id>/
    ├── assets/
    │   └── styles/
    │       └── public.css                # tenant specific CSS, see Styling and assets
    ├── config/
    │   ├── packages/
    │   │   └── doctrine.yaml             # the tenant database connection
    │   └── services.yaml                 # all other tenant parameters
    └── translations/
        └── messages+intl-icu.nl.yaml     # tenant specific texts, see Translations
```

The paths inside `<tenant_id>/` are wired up by convention; there is no registry to add the tenant
to:

- `config/` is loaded by `Kernel::getTenantConfigDir()` in [src/Kernel.php](../../src/Kernel.php).
- `translations/` is the `default_path` in
  [config/packages/translation.yaml](../../config/packages/translation.yaml) (and the same file
  under [apps/publication_api/](../../apps/publication_api/config/packages/translation.yaml)).
- `assets/styles/public.css` is the exception: the tenant has to be listed in
  [tenants/tenants.json](../../tenants/tenants.json) before Vite builds it, see
  [Styling and assets](#styling-and-assets).

A tenant may contain more than this. `minvws`, for example, also has `src/` and `tests/` for its
Covid-19 search theme, legacy controllers, data fixtures and audit loggers. None of that is needed to
get a new tenant running.

One required file lives outside `tenants/`: the Sphinx config for the tenant's user manual, see
[Tenant specific documentation](#tenant-specific-documentation).

### Scaffolding the config

The two config files are pure boilerplate: apart from the Elasticsearch index names, every value is
an environment variable prefixed with the uppercase tenant id. The script below writes both files.

All scripts in this document write paths relative to the current directory, so run them from the
root of the repository. Save the script to a file and run it with `TENANT_ID` set to the new tenant,
for example `TENANT_ID=minocw sh scaffold-config.sh`. Do not paste the scripts into an interactive
shell: `set -eu` would stay active in that shell and close it on the next failing command.

```bash
#!/bin/sh

set -eu

TENANT_ID=${TENANT_ID:?TENANT_ID must be set}

TENANT_UPPER=$(printf '%s' "$TENANT_ID" | tr '[:lower:]' '[:upper:]')
TENANT_LOWER=$(printf '%s' "$TENANT_ID" | tr '[:upper:]' '[:lower:]')

TENANT_DIR="tenants/${TENANT_LOWER}"
mkdir -p "$TENANT_DIR/config/packages"

cat > "$TENANT_DIR/config/packages/doctrine.yaml" << EOF
doctrine:
    dbal:
        url: '%env(resolve:${TENANT_UPPER}_DATABASE_URL)%'
EOF

cat > "$TENANT_DIR/config/services.yaml" << EOF
parameters:
    app_secret: '%env(${TENANT_UPPER}_APP_SECRET)%'
    public_base_url: '%env(${TENANT_UPPER}_PUBLIC_BASE_URL)%'
    site_name: '%env(${TENANT_UPPER}_SITE_NAME)%'
    cookie_name: '%env(${TENANT_UPPER}_COOKIE_NAME)%'
    totp_issuer: '%env(${TENANT_UPPER}_TOTP_ISSUER)%'
    trusted_hosts: '%env(${TENANT_UPPER}_TRUSTED_HOSTS)%'
    database_encryption_key: '%env(${TENANT_UPPER}_DATABASE_ENCRYPTION_KEY)%'

    # Elasticsearch
    elasticsearch.host: '%env(${TENANT_UPPER}_ELASTICSEARCH_HOST)%'
    elasticsearch.user: '%env(${TENANT_UPPER}_ELASTICSEARCH_USER)%'
    elasticsearch.pass: '%env(${TENANT_UPPER}_ELASTICSEARCH_PASS)%'
    elasticsearch.mtls.cert_path: '%env(${TENANT_UPPER}_ELASTICSEARCH_MTLS_CERT_PATH)%'
    elasticsearch.mtls.key_path: '%env(${TENANT_UPPER}_ELASTICSEARCH_MTLS_KEY_PATH)%'
    elasticsearch.mtls.ca_path: '%env(${TENANT_UPPER}_ELASTICSEARCH_MTLS_CA_PATH)%'

    # ElasticSearch indexes
    elasticsearch.index.prefix.default: '${TENANT_LOWER}-'
    elasticsearch.index.prefix: '%env(default:elasticsearch.index.prefix.default:${TENANT_UPPER}_ELASTICSEARCH_INDEX_PREFIX)%'
    elasticsearch.index.read: '%elasticsearch.index.prefix%read'
    elasticsearch.index.write: '%elasticsearch.index.prefix%write'

    # RabbitMQ
    rabbitmq_url: '%env(${TENANT_UPPER}_RABBITMQ_URL)%'
    rabbitmq_stats_url: '%env(${TENANT_UPPER}_RABBITMQ_STATS_URL)%'

    high_transport_dsn: '%env(${TENANT_UPPER}_HIGH_TRANSPORT_DSN)%'
    ingestor_transport_dsn: '%env(${TENANT_UPPER}_INGESTOR_TRANSPORT_DSN)%'
    esupdater_transport_dsn: '%env(${TENANT_UPPER}_ESUPDATER_TRANSPORT_DSN)%'
    global_transport_dsn: '%env(${TENANT_UPPER}_GLOBAL_TRANSPORT_DSN)%'
    api_documents_transport_dsn: '%env(${TENANT_UPPER}_API_DOCUMENTS_TRANSPORT_DSN)%'

    # Minio
    storage.minio.region: '%env(${TENANT_UPPER}_STORAGE_MINIO_REGION)%'
    storage.minio.endpoint: '%env(${TENANT_UPPER}_STORAGE_MINIO_ENDPOINT)%'
    storage.minio.access_key: '%env(${TENANT_UPPER}_STORAGE_MINIO_ACCESS_KEY)%'
    storage.minio.secret_key: '%env(${TENANT_UPPER}_STORAGE_MINIO_SECRET_KEY)%'

    storage.minio.bucket.upload: '%env(${TENANT_UPPER}_STORAGE_MINIO_UPLOAD_BUCKET)%'
    storage.minio.bucket.document: '%env(${TENANT_UPPER}_STORAGE_MINIO_DOCUMENT_BUCKET)%'
    storage.minio.bucket.batch: '%env(${TENANT_UPPER}_STORAGE_MINIO_BATCH_BUCKET)%'
    storage.minio.bucket.woo_index: '%env(${TENANT_UPPER}_STORAGE_MINIO_WOO_INDEX_BUCKET)%'
    storage.minio.bucket.assets: '%env(${TENANT_UPPER}_STORAGE_MINIO_ASSETS_BUCKET)%'

    # Redis
    redis.url: '%env(${TENANT_UPPER}_REDIS_URL)%'
    redis.tls.cafile: '%env(${TENANT_UPPER}_REDIS_TLS_CAFILE)%'
    redis.tls.local_cert: '%env(${TENANT_UPPER}_REDIS_TLS_LOCAL_CERT)%'
    redis.tls.local_pk: '%env(${TENANT_UPPER}_REDIS_TLS_LOCAL_PK)%'

    # Application publication_api specific configs:
    publication_api_ssl_username_whitelist: '%env(string:${TENANT_UPPER}_PUBLICATION_API_SSL_USERNAME_WHITELIST)%'
    publication_api_ssl_organization_identifier: '%env(string:${TENANT_UPPER}_PUBLICATION_API_SSL_ORGANIZATION_IDENTIFIER)%'
EOF
```

## Translations

The shared translations live in [translations/](../../translations/); the tenant catalogue only
overrides the handful of keys that name the organisation. See
[translations.md](translations.md) for the general translation setup, and
[tenants/minbuza/](../../tenants/minbuza/translations/messages+intl-icu.nl.yaml) for a filled-in
example.

| Key                                | Where it shows up                                                           |
| ---------------------------------- | --------------------------------------------------------------------------- |
| `global.domain_title`              | the `<title>` of every public page                                          |
| `global.meta_description`          | the `<meta name="description">` of every public page                        |
| `admin.global.platform_title`      | the heading on the printable credentials page in the admin                  |
| `public.intro.label.oga`           | the first line of the `<h1>` on the public home page                        |
| `public.intro.description.oga`     | no longer referenced by any template; kept for parity between tenants       |
| `public.global.logo.text`          | the text next to the logo in the public header                              |
| `public.department.description`    | the intro on the public department page                                     |
| `public.browse.facets.description` | the intro above the public search filters; rendered raw, so HTML is allowed |

The wording is the same for every tenant; only the name of the organisation differs. The script
below writes the catalogue. Set `ORGANISATION` to the full official name and `DOMAIN_TITLE` to the
short name the organisation wants to be known by on the platform. For MinBuZa those are
`Ministerie van Buitenlandse Zaken` and `OpenBZ`.

```bash
#!/bin/sh

set -eu

TENANT_ID=${TENANT_ID:?TENANT_ID must be set}
ORGANISATION=${ORGANISATION:?ORGANISATION must be set}
DOMAIN_TITLE=${DOMAIN_TITLE:?DOMAIN_TITLE must be set}

TENANT_LOWER=$(printf '%s' "$TENANT_ID" | tr '[:upper:]' '[:lower:]')

TENANT_DIR="tenants/${TENANT_LOWER}/translations"
mkdir -p "$TENANT_DIR"

cat > "$TENANT_DIR/messages+intl-icu.nl.yaml" << EOF
global.domain_title: "${DOMAIN_TITLE}"
global.meta_description: "Op deze website vindt u interne informatie van het ${ORGANISATION}. Deze informatie is openbaar gemaakt om inzicht te geven in de werkzaamheden en beslissingen van het ministerie."
admin.global.platform_title: "Beheer platform woo-publicaties ${ORGANISATION}"
public.intro.label.oga: "${DOMAIN_TITLE}"
public.intro.description.oga: "Openbaar gemaakte informatie door het ${ORGANISATION}"
public.global.logo.text: "${ORGANISATION}"
public.department.description: "Op dit platform vindt u informatie die openbaar is gemaakt op grond van de Wet open overheid door het ${ORGANISATION} en aan haar verwante (zelfstandig) bestuursorganen. Het platform zal de komende periode aangevuld worden met meer informatie."
public.browse.facets.description: "Op dit platform vindt u informatie die openbaar is gemaakt op grond van de <a href='https://www.rijksoverheid.nl/onderwerpen/wet-open-overheid-woo'>Wet open overheid (Woo)</a> door het ${ORGANISATION} en aan haar verwante (zelfstandig) bestuursorganen. Het platform zal de komende periode aangevuld worden met meer informatie."
EOF
```

These are placeholder texts, good enough to get the tenant running. We replace them with the texts
the organisation supplies before the tenant goes to production. The texts assume a ministry (`het
${ORGANISATION}`, `het ministerie`), so they have to be reworded for a tenant that is not one.

## Styling and assets

Every tenant needs its own `assets/styles/public.css`, even when it has nothing to override. The
public layout loads it unconditionally through `vite_entry_link_tags('tenant-' ~ TENANT_ID ~
'-public')` in [templates/public/base.html.twig](../../templates/public/base.html.twig), so a
missing stylesheet breaks the public site.

Two things about this file are easy to get wrong:

- It is **not** picked up by convention, unlike the config and translations. Add the tenant id to
  [tenants/tenants.json](../../tenants/tenants.json):

  ```json
  ["minvws", "minfin", "minbuza", "minocw", "<tenant_id>"]
  ```

  [vite.config.mts](../../vite.config.mts) turns that list into the Rollup entries, so the entry name
  `tenant-<tenant_id>-public` and the path to the stylesheet follow from the id; there is nothing
  else to register. The same list feeds the `ALL_TENANTS` var in
  [Taskfile.dist.yml](../../Taskfile.dist.yml), so a tenant missing here is also skipped by tasks
  that loop over all tenants, such as `docs:build:all`.
  [TenantsJsonTest](../../tests/Unit/Domain/TenantsJsonTest.php) fails when the list and the
  `TenantId` enum disagree.

- It may **not** be empty. Vite drops empty CSS assets, which breaks the entrypoint mapping of
  `vite-plugin-symfony`. The `--tenant` marker in the scaffold below exists to keep the built file
  non-empty; leave it in place even once the tenant has real overrides.

The script below writes the baseline stylesheet, the same one every tenant without style overrides
uses:

```bash
#!/bin/sh

set -eu

TENANT_ID=${TENANT_ID:?TENANT_ID must be set}

TENANT_LOWER=$(printf '%s' "$TENANT_ID" | tr '[:upper:]' '[:lower:]')

TENANT_DIR="tenants/${TENANT_LOWER}/assets/styles"
mkdir -p "$TENANT_DIR"

cat > "$TENANT_DIR/public.css" << EOF
/*
 * Tenant-specific overrides of theme variables for ${TENANT_LOWER}. Every tenant has
 * a stylesheet so it can be loaded unconditionally (see
 * templates/public/base.html.twig). The --tenant marker keeps the built file
 * non-empty: Vite drops empty CSS assets, which breaks the entrypoint
 * mapping of vite-plugin-symfony.
 */

:root {
    --tenant: '${TENANT_LOWER}';
}
EOF
```

Anything the tenant wants to look different goes in the same `:root` block, as an override of a
theme variable from [assets/styles/public/theme.css](../../assets/styles/public/theme.css). The
rules are unlayered on purpose: that is what makes them win over the Tailwind theme layer. The only
tenant doing this today is `minbuza`, which points `--img-woo-hero` at its own image:

```css
:root {
    --tenant: 'minbuza';
    --img-woo-hero: url(/public/img/public/hero/hero-vlaggen-eu-raad-brussel.jpeg);
}
```

## Tenant specific documentation

The gebruikershandleiding in [docs/gebruikershandleiding/](../gebruikershandleiding/) is written once
and built once per tenant with Sphinx, so every tenant gets its own manual with its own hostnames and
its own name in the header. The pages are shared; only a handful of values differ per tenant.

Those values live in a single file, `docs/gebruikershandleiding/tenants/<tenant_id>.py`. This is the
only tenant file that does *not* live under `tenants/<tenant_id>/`, and it is **not optional**:
[conf.py](../gebruikershandleiding/conf.py) raises `RuntimeError` when the file for the tenant being
built is missing, and CI builds the manual for every tenant in
[tenants/tenants.json](../../tenants/tenants.json) (`task docs:build:all`, run from
[package.yml](../../.github/workflows/package.yml)). A tenant added to `tenants.json` without this
file breaks the release build.

How it is wired up:

- [conf.py](../gebruikershandleiding/conf.py) reads the `SPHINX_TENANT` environment variable
  (default `minvws`), loads `tenants/<tenant>.py` and merges it over the base config: `extlinks` and
  `html_theme_options` are merged key by key, so the tenant file only declares what it changes,
  while `myst_substitutions` is taken from the tenant file as a whole.
- [docs/Taskfile.docs.yml](../Taskfile.docs.yml) sets `SPHINX_TENANT` per build and writes the
  result to `docs/_build/<tenant>/`, which `docs:build:local` then copies to
  `public/documentatie/<tenant>/`. The build runs with `--fail-on-warning`.
- `docs:build:local:all` loops over `LOCAL_TENANTS` and `docs:build:all` over `ALL_TENANTS`, see
  [Local development](#local-development).

Three values differ per tenant:

| Value          | Meaning                                                                       |
| -------------- | ----------------------------------------------------------------------------- |
| `_portal_name` | hostname of the public site, by convention `open.<tenant_id>.nl`              |
| `_balie_name`  | hostname of the admin, by convention `balie.<portal_name>`                    |
| `logo_text`    | the full official name of the organisation, shown next to the logo            |

Everything else is derived from those two hostnames: the `portal_*` and `balie_*` MyST substitutions
used in the manual, and the `:public:` and `:balie:` extlink roles. `minvws` is the exception to the
`_balie_name` convention: its admin lives on `balie.woo.irealisatie.nl` for historical reasons. New
tenants follow the convention.

The script below writes the file. Set `PORTAL_NAME` to the public hostname *without* a scheme and
`ORGANISATION` to the full official name:

```bash
#!/bin/sh

set -eu

TENANT_ID=${TENANT_ID:?TENANT_ID must be set}
PORTAL_NAME=${PORTAL_NAME:?PORTAL_NAME must be set}
ORGANISATION=${ORGANISATION:?ORGANISATION must be set}

TENANT_LOWER=$(printf '%s' "$TENANT_ID" | tr '[:upper:]' '[:lower:]')

TENANT_DIR="docs/gebruikershandleiding/tenants"
mkdir -p "$TENANT_DIR"

cat > "$TENANT_DIR/${TENANT_LOWER}.py" << EOF
_portal_name = "$PORTAL_NAME"
_balie_name = "balie.$PORTAL_NAME"

myst_substitutions = {
    "portal_name": f"{_portal_name}",
    "portal_url": f"https://{_portal_name}",
    "portal_link": f"[{_portal_name}](https://{_portal_name})",
    "balie_url": f"https://{_balie_name}/balie",
    "balie_link": f"[https://{_balie_name}/balie](https://{_balie_name}/balie)",
}

html_theme_options = {
    "logo_text": "$ORGANISATION",
}

extlinks = {
    "public": (f"{myst_substitutions['portal_url']}/%s", f"{_portal_name}/%s"),
    "balie": (f"{myst_substitutions['balie_url']}/%s", f"{_balie_name}/balie/%s"),
}
EOF
```

Build the manual for the new tenant to confirm the file is picked up:

```shell
task docs:build TENANT=minocw
```

The build fails when the tenant is not in [tenants/tenants.json](../../tenants/tenants.json) yet,
because `TENANT` is validated against `ALL_TENANTS` (see [Styling and assets](#styling-and-assets)). Use
`task docs:build:local TENANT=minocw` instead to also copy the result to
`public/documentatie/minocw/`, which is what makes it viewable locally.

## Local development

A new tenant is not automatically part of the local development environment. Locally we deliberately
run only two tenants, `minvws` and `minfin`: that is enough to test multi-tenant behaviour, while
avoiding a duplicate set of workers and containers for every tenant we support.

This is controlled by two vars in [Taskfile.dist.yml](../../Taskfile.dist.yml):

- `ALL_TENANTS`: every tenant the application supports. It is read from
  [tenants/tenants.json](../../tenants/tenants.json), so a new tenant ends up here by being added to
  that file (see [Styling and assets](#styling-and-assets)).
- `LOCAL_TENANTS`: the subset that is actually configured and started locally. A new tenant is
  normally *not* added here.

Running a different or an additional tenant locally takes more than adding it to `LOCAL_TENANTS`.
The tenant also needs its own hosts, ports and environment variables, worker and container setup,
and client certificates. The following is part of what is involved, but the list is not necessarily
complete:

- [compose.yml](../../compose.yml), plus the variants in [docker/compose/](../../docker/compose/)
  such as `compose.two.yml`, `compose.orbstack.yml` and `.env.two`
- the per-tenant `<TENANT>_PUBLIC_HOST`, `<TENANT>_ADMIN_HOST` and `<TENANT>_PUBLICATION_API_HOST`
  variables and the `HTTP_HOST_TO_TENANT_MAPPING` in [.env.dev](../../.env.dev), and
  `<TENANT>_PUBLICATION_API_URL` in [Taskfile.dist.yml](../../Taskfile.dist.yml)
- the certificate enrollments in [docker/certs/enrollments/](../../docker/certs/enrollments/)
- the Robot Framework env files in
  [tests/robot_framework/envs/](../../tests/robot_framework/envs/)

## Requesting environments for a new tenant

The remote environments for a new tenant are not set up from this repository. We request them from
the operations team by creating a Zammad ticket (their ticketing system) for *each* environment:
test, acceptance and production.

The [Woo multitenancy wiki](https://github.com/minvws/nl-rdobeheer-ansible/wiki/Woo-multitenancy) of
the Ansible repository has the complete and up-to-date overview of what we must provide. Pay special
attention to the
[Requirements](https://github.com/minvws/nl-rdobeheer-ansible/wiki/Woo-multitenancy#requirements)
section.

## Reference pull requests

- Adding MinBuZa: [#7263](https://github.com/minvws/nl-rdo-woo-web-private/pull/7263)
- Adding MinOCW: [#7913](https://github.com/minvws/nl-rdo-woo-web-private/pull/7913)
- Tenant specific Sphinx documentation: [#7336](https://github.com/minvws/nl-rdo-woo-web-private/pull/7336)
- MinBuZa custom (hero) header: [#7451](https://github.com/minvws/nl-rdo-woo-web-private/pull/7451)
