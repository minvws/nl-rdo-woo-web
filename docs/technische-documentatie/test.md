# Test

<!-- TOC -->
- [Test](#test)
  - [PHPUnit](#phpunit)
  - [Robot Framework](#robot-framework)
    - [Pre-requisites](#pre-requisites)
    - [General setup](#general-setup)
    - [Running the tests in docker](#running-the-tests-in-docker)
    - [Running the tests in a local virtual environment](#running-the-tests-in-a-local-virtual-environment)
      - [Step 1: Install Python](#step-1-install-python)
      - [Step 2: Install Robot Framework](#step-2-install-robot-framework)
      - [Step 3: Run tests locally](#step-3-run-tests-locally)
    - [Test and Acceptance tests](#test-and-acceptance-tests)
    - [Linting and formatting](#linting-and-formatting)
    - [Resetting test data](#resetting-test-data)
    - [Dependency management](#dependency-management)
  - [Schemathesis](#schemathesis)
<!-- TOC -->

## PHPUnit

The unit and integration tests are written with PHPUnit. They live alongside the code they cover:

| Path                          | Namespace               |
|-------------------------------|-------------------------|
| `tests/`                      | `Shared\Tests\`         |
| `apps/admin/tests/`           | `Admin\Tests\`          |
| `apps/public/tests/`          | `Public\Tests\`         |
| `apps/publication_api/tests/` | `PublicationApi\Tests\` |
| `apps/worker/tests/`          | `Worker\Tests\`         |
| `tenants/minvws/tests/`       | `WooMinVWS\Tests\`      |
| `utils/tests/`                | `Utils\Tests\`          |

All but `utils/tests` have `Unit/` and `Integration/` subdirectories, which `phpunit.xml.dist` groups into three test
suites: `unit`, `integration` and `utils` (the last holding the custom PHPStan rules and Rector rules).

Run everything the way CI does:

```shell
task ci:test
```

Arguments are passed straight through to PHPUnit:

```shell
task ci:test -- --testsuite unit
task ci:test -- --filter TenantResolverTest
task ci:test -- apps/admin/tests/Unit
```

The integration tests need a migrated test database; `task setup` and `task refresh` both run
`task console:migrate:test` for every local tenant.

## Robot Framework

Robot framework is used for the Woo project to execute E2E tests and mainly uses the [Browser library](https://robotframework-browser.org) and several other libraries.

### Pre-requisites

Make sure Woo is running locally. Read the [development_install](development_install.md) instructions first, only applying steps 1 to 4, you can skip step 5

### General setup

All tests are run under a user called Robot Admin, which has 'super admin' rights, username `email@example.org` and
password `IkLoopNooitVastVandaag`. The password is fixed and committed, as `ADMIN_PASSWORD` in
`tests/robot_framework/envs/.env.rf.{local,docker}.{minvws,minfin}`. The OTP secret is generated per tenant.

The creation of this user is handled by:

```bash
task rf:init
```

That does three things per local tenant (minvws and minfin):

1. loads the E2E fixtures;
2. runs `woopie:user:create`, which generates a **temporary** password and an OTP secret, and writes them to
   `secrets/.temp_password` and `secrets/.otp_secret_<tenant>`;
3. runs the `first-login` suite (`tests/sequential/00__Setup.robot`), which logs in with that temporary password,
   changes it to `ADMIN_PASSWORD`, and deletes `secrets/.temp_password`.

So the temporary password is transient; from that point on everything logs in with the fixed one. Only the OTP secrets
survive, in `secrets/.otp_secret_minvws` and `secrets/.otp_secret_minfin`. The `secrets/` directory is gitignored.

Test users that the suites create for other roles get the same fixed password, through the `${new_password}` default on
the `Login Admin` keyword in `resources/Admin.resource`.

`task rf:init` is a no-op once both OTP secret files exist, and it is called automatically by the `rf:local:run*`
tasks. It also disables the Symfony debug toolbar by setting `ENABLE_TOOLBAR=false` in `.env.dev.local`.

If you ever need an OTP code to login manually, use either of the following command:

```shell
task rf:local:otp
task rf:docker:otp
```

### Running the tests in docker

Make sure Woo is running locally. Read the [development_install](development_install.md) instructions first.
Then you can use the following commands:

```bash
# Specific tags
task rf:docker:run tag=testdossiers

# Run the CI set, which covers the web UI
task rf:docker:run:ci

# Run the API set
task rf:docker:run:api

# Open a shell in the robot container
task rf:docker:shell
```

Every `rf:*:run*` task accepts the same variables:

| Variable  | Meaning                                          | Default         |
|-----------|--------------------------------------------------|-----------------|
| `tag`     | Robot tag to include                             | varies per task |
| `exclude` | Robot tag to exclude                             | none            |
| `tenant`  | Which tenant to test against (`minvws`/`minfin`) | `minvws`        |

For example, `task rf:docker:run tag=testdossiers tenant=minfin`.

### Test and Acceptance tests

Running the tests towards the Test and Acceptance environments requires an env file per environment and tenant, in
`tests/robot_framework/envs/`. `libraries/tenants.py` loads
`envs/.env.rf.${ENVIRONMENT}.${TENANT}` for `minvws` and `minfin`, so you need:

```bash
tests/robot_framework/envs/.env.rf.test.minvws
tests/robot_framework/envs/.env.rf.test.minfin
tests/robot_framework/envs/.env.rf.acc.minvws
tests/robot_framework/envs/.env.rf.acc.minfin
```

These are gitignored (`envs/.env.rf.test.*` and `envs/.env.rf.acc.*`). Use the committed
`envs/.env.rf.local.minvws` as a template — the format is identical, with three extra keys that only the test and acc
environments use:

```bash
HEADLESS=true

URL_PUBLIC=
URL_ADMIN=          # include the /balie path, as the local file does
URL_API=

CLIENT_CERT=
CLIENT_KEY=
CA_BUNDLE=

# test and acc only: these environments sit behind basic auth
HTACCESS_USERNAME=
HTACCESS_PASSWORD=

ADMIN_EMAIL=
ADMIN_PASSWORD=
ADMIN_SECRET=       # test and acc only: the OTP secret
```

Locally and in docker the OTP secret is read from `secrets/.otp_secret_<tenant>` instead, which is why `ADMIN_SECRET`
does not appear in the committed env files.

Values starting with `./` or `../` are resolved to absolute paths by `libraries/tenants.py`, against
`tests/robot_framework/` when running in the robot container (where the certificates are mounted) and against the repo
root when running locally. So the same `CLIENT_CERT=./certs/...` value works in both.

You also need the client certificates for those environments, under `certs/test/` and `certs/acc/`. Both directories
exist but ship empty; `CLIENT_CERT`, `CLIENT_KEY` and `CA_BUNDLE` point at whichever files you are given. For reference,
`task certs:gen` produces this trio for local use in `certs/dev/`:

```bash
certs/dev/client-minvws.pem     # CLIENT_CERT
certs/dev/client-minvws.key     # CLIENT_KEY
certs/dev/client-bundle.pem     # CA_BUNDLE
```

> The [Bruno collection](../bruno-collection/README.md) has its own certificate configuration in
> `docs/bruno-collection/bruno.json`, and for test and acc it expects a **combined** certificate:
> `certs/<env>/client-<tenant>-combined.pem` together with `certs/<env>/client-<tenant>.key`. For local use it points at
> the same `certs/dev/client-<tenant>.pem` pair that Robot Framework uses. So a `-combined.pem` in `certs/test/` or
> `certs/acc/` is there for Bruno, not for these tests.

Both the env files and the certificates can be collected through team members.

To run the tests towards either environment, use the following commands:

```shell
task rf:test:run tag=testdossiers
task rf:acc:run tag=testdossiers

# API sets
task rf:test:run:api
task rf:acc:run:api
```

These runs exclude the `skip-test-acc` tag.

### Running the tests in a local virtual environment

#### Step 1: Install Python

- Download and install Python <https://www.python.org/downloads/> — the exact version is pinned by `requires-python` in
  [pyproject.toml](../../tests/robot_framework/pyproject.toml)
- Download and install uv <https://docs.astral.sh/uv/>
- Download and install npm
- Download and install zbar

`uv` manages the virtual environment and the Python version, so pip is not needed.

#### Step 2: Install Robot Framework

Execute the following command. It creates the virtual environment, installs the Robot Framework and all the required
Python libraries declared in [pyproject.toml](../../tests/robot_framework/pyproject.toml), and initialises the Browser
library:

```shell
task rf:local:venv
```

#### Step 3: Run tests locally

To execute a testsuite locally without Docker, pass its tag with `tag=`. Tags are declared in the `*** Settings ***`
section of each `.robot` file under `tests/robot_framework/tests/`, with `Test Tags` (or per test case with `[Tags]`).
For example, [tests/sequential/02\_\_TestDossiers.robot](../../tests/robot_framework/tests/sequential/02__TestDossiers.robot)
declares `ci  testdossiers  public-init  sitemap-init  test-acc`, so `tag=testdossiers` selects it.

The suites are grouped into `tests/sequential/`, `tests/parallel/` and `tests/api/`.

This command will always run the tests sequential.

```shell
task rf:local:run tag=testdossiers
```

The API set runs in parallel with pabot, and there is a mobile-viewport variant:

```shell
task rf:local:run:api
task rf:local:run:mobile
```

### Linting and formatting

Robocop lints and formats the `.robot` files. Its rules are configured in the `[tool.robocop.*]` sections of
[pyproject.toml](../../tests/robot_framework/pyproject.toml). Both tasks need the local virtual environment from
`task rf:local:venv`.

```shell
task rf:lint
task rf:format
```

### Resetting test data

```shell
# Clear dossiers, inquiries, the ES index and the MinIO buckets
task rf:cleansheet

# Reload only the E2E fixtures
task rf:fixtures:load:e2e
```

### Dependency management

The Robot Framework dependencies are declared in
[pyproject.toml](../../tests/robot_framework/pyproject.toml) and locked in `uv.lock`. To add a dependency, add it to the
`dependencies` list in `pyproject.toml` and re-lock. To upgrade everything to the latest allowed versions:

```bash
task rf:dependency:upgrade
```

## Schemathesis

[Schemathesis](https://schemathesis.readthedocs.io/) fuzzes the Publication API against its own OpenAPI schema. It
exports the schema, then runs stateful tests over `https://publication-api-minvws.local`, authenticating with the local
mTLS client certificate from `task certs:gen`.

```shell
task st:run
task st:view
```

Reports, including an HTML schema-coverage report, are written to `tests/schemathesis/reports/`.
