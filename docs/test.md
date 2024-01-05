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

## E2E Coverage

TST & ACC runs every night. CI is executed after a PR.
|        |                                                   | CI                 | TST                | ACC                           |
|--------|---------------------------------------------------|--------------------|--------------------|-------------------------------|
| Portal |                                                   |                    |                    |                               |
|        | Zoeken                                            | :white_check_mark: | :white_check_mark: | :white_check_mark:            |
|        | Zoekresultaten filteren                           | :construction:     |                    | :construction:                |
|        | Document overzichtpagina                          | :white_check_mark: |                    |                               |
|        | Document downloaden                               | :white_check_mark: |                    | :white_check_mark:            |
|        | Besluitdossier overzichtpagina                    | :white_check_mark: |                    |                               |
|        | Besluitdossier filteren                           | :white_check_mark: |                    |                               |
|        | Besluitdossier downloaden                         | :white_check_mark: |                    | :white_check_mark:            |
|        | Besluitbrief downloaden                           | :white_check_mark: |                    | :white_check_mark:            |
|        | Inventarislijst downloaden                        | :white_check_mark: |                    | :white_check_mark:            |
| Balie  |                                                   |                    |                    |                               |
|        | Inlogmodule                                       | :white_check_mark: | :white_check_mark: | :white_check_mark:            |
|        | Besluitdossier filteren                           | :construction:     |                    | :construction:                |
|        | Besluitdossier zoeken                             | :white_check_mark: | :white_check_mark: |                               |
|        | Besluitdossier aanmaken                           | :white_check_mark: | :white_check_mark: |                               |
|        | Besluitdossier documenten intrekken               | :white_check_mark: |                    |                               |
|        | Besluitdossier documenten vervangen               | :white_check_mark: |                    |                               |
|        | Gebruikers aanmaken, bijwerken, ww reset, 2fa     | :construction:     | :construction:     |                               |
|        | Authorisatie (rollen toekennen, (de)activeren)    | :construction:     | :construction:     |                               |
|        | Bestuursorganen beheren                           | :construction:     | :construction:     |                               |
|        | Zakenpagina (documenten/besluiten (ont)koppelen)  | :construction:     | :construction:     |                               |
