# Test

- [Test](#test)
  - [Robot Framework](#robot-framework)
  - [Running the tests in docker](#running-the-tests-in-docker)
  - [Running the tests in a local virtual environment](#running-the-tests-in-a-local-virtual-environment)
    - [Step 1: Install Python](#step-1-install-python)
    - [Step 2: Install Robot Framework](#step-2-install-robot-framework)
    - [Step 3: Run tests locally](#step-3-run-tests-locally)
      - [Test and Acceptance tests](#test-and-acceptance-tests)
  - [Dependency management](#dependency-management)

## Robot Framework

Robot framework is used for the Woo Project to execute E2E tests and mainly uses the [Browser library](https://robotframework-browser.org) and several other libraries.

All tests are run under a user called Robot Admin, which has 'super admin' rights, username `email@example.org` and password `IkLoopNooitVastVandaag`.
The creation of this user is handled by the following command:

```bash
task rf:init
```

If you ever need an OTP code to login manually, use either of the following command:

```shell
task rf:local:otp
task rf:docker:otp
```

## Running the tests in docker

Make sure Woo is running locally. Read the [development_install](development_install.md) instructions first.
Then you can use the following commands:

```bash
# Specific tags
task rf:docker:run tag=testdossiers

# Run the CI set, which covers the web UI
task rf:docker:run:ci

# Run the API set
task rf:docker:run:api
```

## Running the tests in a local virtual environment

Make sure Woo is running locally. Read the [development_install](development_install.md) instructions first.

### Step 1: Install Python

- Download and install Python 3.12.x <https://www.python.org/downloads/>
- Download and install pip <https://pypi.org/project/pip/>
- Download and install npm
- Download and install zbar
- Download and install uv

### Step 2: Install Robot Framework

Execute the following commands. This command will automatically install the Robot Framework and all the required Python Libraries listed in [requirements.txt](../tests/robot_framework/requirements.txt)

```shell
task rf:local:venv
```

### Step 3: Run tests locally

To execute any other testsuite locally without Docker, provide the testsuite's tag as parameter to the command mentioned above. The testsuite's tag can be found at the top of each `.robot` file, as can be seen here: [https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26](https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26)

This command will always run the tests sequential.

```shell
task rf:local:run tag=testdossiers
```

#### Test and Acceptance tests

Running the test towards the Test and Acceptance environments requires the following files:

```bash
/tests/robot_framework/.env.rf.test
/tests/robot_framework/.env.rf.acc
```

They should contain the following values:

```bash
ENVIRONMENT=acc

HEADLESS=true

URL_PUBLIC=
URL_ADMIN=

HTACCESS_USERNAME=
HTACCESS_PASSWORD=
ADMIN_EMAIL=
ADMIN_PASSWORD=
ADMIN_SECRET=

URL_API=

CLIENT_CERT=
CLIENT_KEY=
CA_BUNDLE=

```

Secrets can be collected through team members.

To run the tests towards either environment, use the following commands:

```shell
task rf:test:run tag=testdossiers
task rf:acc:run tag=testdossiers
```

## Dependency management

The dependencies for Robot Framework in this project are managed using a `requirements.in` and `requirements.txt` file.
To add dependencies, add them to the `requirements.in` file and run the following command to generate/update the `requirements.txt`:

```bash
task rf:dependency:upgrade
```
