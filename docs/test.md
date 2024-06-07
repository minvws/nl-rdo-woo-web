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
    - [Test and Acceptance tests](#test-and-acceptance-tests)

## All use cases

We have written all use cases in [test-use-cases.md](test-use-cases.md).

## Robot Framework

Robot framework is used for the WOO Project to execute E2E tests and mainly uses the [Browser Library](https://robotframework-browser.org) and several other [libraries](../tests/robot_framework/Libraries.resource) for specific situations.

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

Make sure WOO is running locally. Read the [install](install.md) instructions first.

## Step 1: Install Python

- Download and install Python 3.12.x <https://www.python.org/downloads/>
- Download and install pip <https://pypi.org/project/pip/>

## Step 2: Install Robot Framework

Execute the following commands. This command will automatically install the Robot Framework and all the required Python Libraries listed in [requirements.txt](../tests/robot_framework/requirements.txt)

```shell
task rf:venv
```

## Step 3: Initialize the application

To run CI tests locally you need to create a initial user with 'super admin'-rights, username `email@example.org` and password `IkLoopNooitVastVandaag`.
This can be done as described in the [install](install.md) instructions, or you can run the following testcase:

```shell
task rf:test tag=init
```

This will create the user with username `email@example.org`, password `IkLoopNooitVastVandaag` and stores the OTP secret in your `~/zshrc` as environment variable `SECRET_WOO_LOCAL`.
You only have to do once.

Note that you have to restart your shell for any following `task rf:test` calls, otherwise it won't know the new secret.

```shell
export SECRET_WOO_LOCAL=<otp secret here>
```

If you ever need an OTP code to login manually, use the following testcase:

```shell
task rf:test tag=otp
```

## Step 4: Run tests locally

### CI tests

To execute the CI tests use the following command:

```shell
task rf:test
task rf:test-headless
```

### Test and Acceptance tests

TST and ACC requires basic authentication to access it. To run this tests locally You need to have the following environment variables set locally. You only need to do this once. Ask a teammember for the values.

```shell
 export USERNAME_WOO_STAGING=
 export PASSWORD_WOO_STAGING=
 export USERNAME_WOO_TEST=
 export PASSWORD_WOO_TEST=
 export EMAIL_WOO_TEST_BALIE=
 export PASSWORD_WOO_TEST_BALIE=
 export SECRET_WOO_TEST_BALIE=
```

To execute the TST tests use the following command:

```shell
task rf:test TAG=tst
task rf:test-headless TAG=acc
```

To execute the ACC tests use the following command:

```shell
task rf:test TAG=E2E_ACC
task rf:test-headless TAG=E2E_ACC
```
