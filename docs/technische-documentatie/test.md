# Test

- [Test](#test)
  - [All use cases](#all-use-cases)
  - [Robot Framework](#robot-framework)
  - [Install Robot Framework](#install-robot-framework)
  - [Step 1: Install Python](#step-1-install-python)
  - [Step 2: Install Robot Framework](#step-2-install-robot-framework)
  - [Step 3: Initialize the application](#step-3-initialize-the-application)
  - [Step 4: Run tests locally](#step-4-run-tests-locally)
    - [CI tests](#ci-tests)
    - [Any other testsuite](#any-other-testsuite)
    - [Test and Acceptance tests](#test-and-acceptance-tests)
  - [Dependency management](#dependency-management)

## All use cases

We have written all use cases in [test-use-cases.md](test-use-cases.md).

## Robot Framework

Robot framework is used for the Woo Project to execute E2E tests and mainly uses the [Browser Library](https://robotframework-browser.org) and several other [libraries](../tests/robot_framework/Libraries.resource) for specific situations.

Additional resources:

- [Robot Framework Slack](https://rf-invite.herokuapp.com)
- [Robot Framework IDE setup](https://docs.robotframework.org/docs/getting_started/ide)
- [Robot Framework BuiltIn Keywords](https://robotframework.org/robotframework/latest/libraries/BuiltIn.html)
- [Browser Library Keywords](https://marketsquare.github.io/robotframework-browser/Browser.html)
- [DebugLibrary](https://github.com/xyb/robotframework-debuglibrary/)
- [Operating System Keywords](https://robotframework.org/robotframework/latest/libraries/OperatingSystem.html)
- [OTP Library](https://github.com/formulatedautomation/robotframework-otp?tab=readme-ov-file)
- [Process Library](https://robotframework.org/robotframework/latest/libraries/Process.html)
- [String Library](https://robotframework.org/robotframework/latest/libraries/String.html)

## Install Robot Framework

Make sure WOO is running locally. Read the [development_install](development_install.md) instructions first.

## Step 1: Install Python

- Download and install Python 3.12.x <https://www.python.org/downloads/>
- Download and install pip <https://pypi.org/project/pip/>
- Download and install npm
- Download and install zbar

## Step 2: Install Robot Framework

Execute the following commands. This command will automatically install the Robot Framework and all the required Python Libraries listed in [requirements.txt](../tests/robot_framework/requirements.txt)

```shell
task rf:venv
```

## Step 3: Initialize the application

To run CI tests locally you need to create a user with 'super admin'-rights, username `email@example.org` and password `IkLoopNooitVastVandaag`.
To prepare for this, you should create an environment variable named `SECRET_WOO_LOCAL` in your  `~/.zshrc` file, for which the value will be automatically replaced later on:

```shell
export SECRET_WOO_LOCAL=REPLACEABLE
```

Then create the aforementioned admin user by either follow the instructions in [development_install](development_install.md) or running the following testcase:

```shell
task rf:run-local tag=init
```

Note that you have to restart your shell for any following `task rf:test` calls, otherwise it won't know the new secret.

If you ever need an OTP code to login manually, use the following testcase:

```shell
task rf:run-local tag=otp
```

## Step 4: Run tests locally

### CI tests

To execute the CI tests use the following command:

```shell
task rf:run-local tag=ci
```

### Any other testsuite

To execute any other testsuite locally, provide the testsuite's tag as parameter to the command mentioned above. The testsuite's tag can be found at the top of each `.robot` file, as can be seen here: [https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26](https://github.com/minvws/nl-rdo-woo-web-private/blob/13d615779580699d743ff24a1f7ff45ae2eb9111/tests/robot_framework/tests/02__TestDossiers.robot#L26)

```shell
task rf:run-local tag=testdossiers
```

### Test and Acceptance tests

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
task rf:run-test tag=pr
```

To execute the ACC tests use the following command:

```shell
task rf:run-acc tag=pr
```

## Dependency management

The dependencies for Robot Framework in this project are managed using a `requirements.in` and `requirements.txt` file.
To add dependencies, add them to the `requirements.in` file and run `dependency:export`, which will generate/update the `requirements.txt`.
Installation of the necessary dependencies is handled through the commands mentioned above.
