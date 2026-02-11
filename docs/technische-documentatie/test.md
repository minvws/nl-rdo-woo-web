# Test

- [Test](#test)
  - [Use cases](#use-cases)
  - [Robot Framework](#robot-framework)
  - [Running the tests in docker](#running-the-tests-in-docker)
  - [Running the tests in a local virtual environment](#running-the-tests-in-a-local-virtual-environment)
    - [Step 1: Install Python](#step-1-install-python)
    - [Step 2: Install Robot Framework](#step-2-install-robot-framework)
    - [Step 3: Initialize the application](#step-3-initialize-the-application)
    - [Step 4: Run tests locally](#step-4-run-tests-locally)
      - [Test and Acceptance tests](#test-and-acceptance-tests)
  - [Dependency management](#dependency-management)

## Use cases

We have written some use cases in [test-cases.md](test-cases.md).

## Robot Framework

Robot framework is used for the Woo Project to execute E2E tests and mainly uses the [Browser library](https://robotframework-browser.org) and several other libraries.

## Running the tests in docker

Make sure Woo is running locally. Read the [development_install](development_install.md) instructions first.
Then run the following commands:

```bash
task rf:docker:init
```

Then run specific tags using the this commands:

```bash
task rf:docker:run tag=testdossiers
```

Or run the CI set for the web app:

```bash
task rf:docker:run:ci
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

### Step 3: Initialize the application

To run CI tests locally you need to create a user with 'super admin'-rights, username `email@example.org` and password `IkLoopNooitVastVandaag`.

Then create the aforementioned admin user by either following the instructions in [development_install](development_install.md) or running the following command. Note that this is still headful, it does not user Docker.

```shell
task rf:local:init
```

If you ever need an OTP code to login manually, use the following command:

```shell
task rf:otp
```

### Step 4: Run tests locally

To execute any other testsuite locally without Docker, provide the testsuite's tag as parameter to the command mentioned above. The testsuite's tag can be found at the top of each `.robot` file, as can be seen here: [https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26](https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26)

This command will always run the tests sequential.

```shell
task rf:local:run tag=testdossiers
```

#### Test and Acceptance tests

TST and ACC requires basic authentication to access it. To run this tests locally You need to have the following environment variables set locally. You only need to do this once. Ask a teammember for the values.

```shell
export USERNAME_WOO_TEST=
export PASSWORD_WOO_TEST=
export EMAIL_WOO_TEST_BALIE=
export PASSWORD_WOO_TEST_BALIE=
export SECRET_WOO_TEST_BALIE=
export USERNAME_WOO_STAGING=
export PASSWORD_WOO_STAGING=
export EMAIL_WOO_STAGING_BALIE=
export PASSWORD_WOO_STAGING_BALIE=
export SECRET_WOO_STAGING_BALIE=
```

To execute the TST tests use the following command:

```shell
task rf:test:run tag=testdossiers
```

To execute the ACC tests use the following command:

```shell
task rf:acc:run tag=testdossiers
```

## Dependency management

The dependencies for Robot Framework in this project are managed using a `requirements.in` and `requirements.txt` file.
To add dependencies, add them to the `requirements.in` file and run the following command to generate/update the `requirements.txt`:

```bash
task rf:dependency:upgrade
```
