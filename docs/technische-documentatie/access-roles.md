# Woo Platform Authorization matrix

<!-- TOC -->
- [Woo Platform Authorization matrix](#woo-platform-authorization-matrix)
  - [User](#user)
  - [Department](#department)
  - [Dossier](#dossier)
  - [Document](#document)
  - [Organisation](#organisation)
  - [Inquiry](#inquiry)
  - [Misc](#misc)
<!-- TOC -->

Source of this information: [auth_matrix.yaml](../../config/packages/auth_matrix.yaml)

## User

| **Prefix** | **Roles**               | **Permissions**                                      | **Filters**              |
|------------|-------------------------|------------------------------------------------------|--------------------------|
| user       | ROLE_ORGANISATION_ADMIN | create: true, read: true, update: true, delete: true | organisation_only: true  |
| user       | ROLE_GLOBAL_ADMIN       | create: true, read: true, update: true, delete: true | organisation_only: false |
| user       | ROLE_SUPER_ADMIN        | create: true, read: true, update: true, delete: true | organisation_only: false |

## Department

| **Prefix** | **Roles**         | **Permissions**                         |                          |
|------------|-------------------|-----------------------------------------|--------------------------|
| department | ROLE_GLOBAL_ADMIN | create: false, read: true, update: true |                          |
| department | ROLE_SUPER_ADMIN  | create: true, read: true, update: true  |                          |

## Dossier

| **Prefix** | **Roles**          | **Permissions**                                                                           | **Filters**                                           |
|------------|--------------------|-------------------------------------------------------------------------------------------|-------------------------------------------------------|
| dossier    | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: false                                     | published_dossiers: true                              |
| dossier    | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: true                                      | published_dossiers: false, unpublished_dossiers: true |
| dossier    | ROLE_VIEW_ACCESS   | create: false, read: true, update: false, delete: false                                   | published_dossiers: true, unpublished_dossiers: true  |
| dossier    | ROLE_SUPER_ADMIN   | create: true, read: true, update: true, delete: true, execute: true, administration: true | published_dossiers: true, unpublished_dossiers: true  |
| dossier    | ROLE_GLOBAL_ADMIN  | create: true, read: true, update: true, delete: false, execute: true                      | published_dossiers: true                              |
| dossier    | ROLE_GLOBAL_ADMIN  | create: true, read: true, update: true, delete: true, execute: true                       | unpublished_dossiers: true                            |

## Document

| **Prefix** | **Roles**          | **Permissions**                                         | **Filters**                                           |
|------------|--------------------|---------------------------------------------------------|-------------------------------------------------------|
| document   | ROLE_SUPER_ADMIN   | create: true, read: true, update: true, delete: true    | published_dossiers: true, unpublished_dossiers: true  |
| document   | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: true    | published_dossiers: false, unpublished_dossiers: true |
| document   | ROLE_DOSSIER_ADMIN | create: true, read: true, update: true, delete: false   | published_dossiers: true, unpublished_dossiers: false |
| document   | ROLE_GLOBAL_ADMIN  | create: true, read: true, update: true, delete: true    | published_dossiers: false, unpublished_dossiers: true |
| document   | ROLE_GLOBAL_ADMIN  | create: true, read: true, update: true, delete: false   | published_dossiers: true, unpublished_dossiers: false |
| document   | ROLE_VIEW_ACCESS   | create: false, read: true, update: false, delete: false | published_dossiers: true, unpublished_dossiers: false |

## Organisation

| **Prefix**   | **Roles**         | **Permissions**                                      |
|--------------|-------------------|------------------------------------------------------|
| organisation | ROLE_GLOBAL_ADMIN | create: true, read: true, update: true, delete: true |
| organisation | ROLE_SUPER_ADMIN  | create: true, read: true, update: true, delete: true |

## Inquiry

| **Prefix** | **Roles**               | **Permissions**                                                            | **Filters**                                          |
|------------|-------------------------|----------------------------------------------------------------------------|------------------------------------------------------|
| inquiry    | ROLE_SUPER_ADMIN        | create: true, read: true, update: true, delete: true, administration: true | published_dossiers: true, unpublished_dossiers: true |
| inquiry    | ROLE_GLOBAL_ADMIN       | create: true, read: true, update: true, delete: false                      | published_dossiers: true, unpublished_dossiers: true |
| inquiry    | ROLE_ORGANISATION_ADMIN | create: true, read: true, update: true, delete: false                      | published_dossiers: true, unpublished_dossiers: true |
| inquiry    | ROLE_DOSSIER_ADMIN      | create: true, read: true, update: true, delete: false                      | published_dossiers: true, unpublished_dossiers: true |
| inquiry    | ROLE_VIEW_ACCESS        | create: false, read: true, update: false, delete: false                    |                                                      |

## Misc

| **Prefix** | **Roles**               | **Permissions**                                         |
|------------|-------------------------|---------------------------------------------------------|
| stat       | ROLE_GLOBAL_ADMIN       | create: false, read: true, update: false, delete: false |
| stat       | ROLE_ORGANISATION_ADMIN | create: false, read: true, update: false, delete: false |
| stat       | ROLE_SUPER_ADMIN        | create: true, read: true, update: true, delete: true    |
| elastic    | ROLE_SUPER_ADMIN        | create: true, read: true, update: true, delete: true    |
