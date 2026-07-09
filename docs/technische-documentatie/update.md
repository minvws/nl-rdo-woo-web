# Woo Publication Platform

<!-- TOC -->
- [Woo Publication Platform](#woo-publication-platform)
  - [After pulling new changes or switching branches](#after-pulling-new-changes-or-switching-branches)
  - [Local setup troubleshooting](#local-setup-troubleshooting)
<!-- TOC -->

## After pulling new changes or switching branches

Whenever you pull new changes or switch branches, you should run the following command:

```shell
task refresh
```

This will pull Composer and NPM dependencies, update the database (migrations), build the front-end and sync the `.env`-file.

## Local setup troubleshooting

If you still get errors after running `task refresh`, try the following:

```shell
task reset
```

This will remove all project related (Docker) containers, volumes and networks.
