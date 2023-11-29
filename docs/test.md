# Test

## Robot Framework

Robot framework is used for the Woo Project to execute CI and E2E tests.

## Install Robot Framework

Make sure Woo is running locally. Read the [install](install.md) instructions first. Robot framework runs on the Python framework. Instal

## Step 1: Install Python

Download and install Python 3.8.x <https://www.python.org/downloads/>

## Step 2: Install Robot Framework

Execute the following command from the Makefile. This command will automatically install the Robot Framework in a virtual Python environment. It will also install the required Python libraries listed in requirements.txt

```shell
    make install-rf
```

## Step 3: Run tests

The tests are located in test/robot_framework/. To execute the CI tests use the following command:

```shell
    make test-rf-head/CI
```

To execute the CI tests headless use the following command:

```shell
    make test-rf/CI
```

To execute the E2E tests use the following command:

```shell
    make test-rf-head/E2E
```

To execute the E2E tests headless use the following command:

```shell
    make test-rf/E2E
```
