# Test

- [All use cases](#all-use-cases)
- [Robot Framework](#robot-framework)
- [Install Robot Framework](#install-robot-framework)
- [Step 1: Install Python](#step-1-install-python)
- [Step 2: Install Robot Framework](#step-2-install-robot-framework)
- [Step 3: Run CI tests locally](#step-3-run-ci-tests-locally)
- [Step 4: Run TST and ACC tests locally](#step-4-run-tst-and-acc-tests-locally)
- [E2E Coverage](#e2e-coverage)

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

## Step 3: Run CI tests locally

To run CI tests locally you need to create a initial user with 'super admin'-rights as described in the [install](install.md) instructions. You only have to do once. Make sure they have following username/password

```shell
email@example.org
IkLoopNooitVastVandaag
```

and set the OTP secret in your environment variables:

```shell
export SECRET_WOO_LOCAL=<otp secret here>
```

To execute the CI tests use the following command:

```shell
task rf:test
task rf:test-headless
```

## Step 4: Run TST and ACC tests locally

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
task rf:test TAG=E2E_TST
task rf:test-headless TAG=E2E_TST
```

To execute the ACC tests use the following command:

```shell
task rf:test TAG=E2E_ACC
task rf:test-headless TAG=E2E_ACC
```

## E2E Coverage

TST & ACC runs every night. CI is currently disabled.

|        |                                       | CI                 | TST                | ACC                           |
|--------|---------------------------------------|--------------------|--------------------|-------------------------------|
| Portal |                                       |                    |                    |                               |
|        | Search                                | :white_check_mark: | :white_check_mark: | :white_check_mark:            |
|        | Filter search results                 | :white_check_mark: |                    | :white_check_mark:            |
|        | Document overview page                | :white_check_mark: |                    | :white_check_mark:            |
|        | Related documents                     | :white_check_mark: |                    | :white_check_mark:            |
|        | Download document                     | :white_check_mark: |                    | :white_check_mark:            |
|        | Besluitdossier overview page          | :white_check_mark: |                    | :white_check_mark:            |
|        | Filter Besluitdossier                 | :white_check_mark: |                    |                               |
|        | Download Besluitdossier               | :white_check_mark: |                    |                               |
|        | Download Besluitdossier (small)       |                    |                    | :white_check_mark:            |
|        | Download Besluitdossier (large)       |                    |                    | :white_check_mark:            |
|        | Download Besluitbrief                 | :white_check_mark: |                    | :white_check_mark:            |
|        | Download Inventarislijst              | :white_check_mark: |                    | :white_check_mark:            |
| Balie  |                                       |                    |                    |                               |
|        | Login module                          | :white_check_mark: | :white_check_mark: | :white_check_mark:            |
|        | Filter Besluitdossier                 | :white_check_mark: | :white_check_mark: |                               |
|        | Search Besluitdossier                 | :white_check_mark: | :white_check_mark: |                               |
|        | Create Besluitdossier                 | :white_check_mark: | :white_check_mark: |                               |
|        | Delete Besluitdossier                 |                    | :white_check_mark: |                               |
|        | Retract Besluitdossier documenten     | :white_check_mark: |                    |                               |
|        | Replace Besluitdossier documenten     | :white_check_mark: |                    |                               |
|        | Create user                           | :white_check_mark: |                    |                               |
|        | Edit user                             | :white_check_mark: |                    |                               |
|        | Password reset user                   | :white_check_mark: |                    |                               |
|        | 2FA reset user                        | :white_check_mark: |                    |                               |
|        | (De)Activate user                     | :white_check_mark: |                    |                               |
|        | Change user role                      | :white_check_mark: |                    |                               |
|        | Bestuursorganen beheren               | :construction:     |                    |                               |
|        | Link documents Zakenpagina            | :white_check_mark: |                    |                               |
|        | Link besluit Zakenpagina              | :white_check_mark: |                    |                               |
