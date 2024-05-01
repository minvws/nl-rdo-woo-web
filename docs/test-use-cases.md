# Test

- [Test cases Public Website](#test-cases-public-website)
  - [Public Website](#public-website)
- [Test cases Private Admin (Balie)](#test-cases-private-admin-balie)
  - [Private Admin (Balie)](#private-admin-balie)
    - [Role Super Admin (ROLE_SUPER_ADMIN)](#role-super-admin-role_super_admin)
    - [Role Global Admin (ROLE_GLOBAL_ADMIN)](#role-global-admin-role_global_admin)
    - [Role Organisation Admin (ROLE_ORGANISATION_ADMIN)](#role-organisation-admin-role_organisation_admin)
    - [Role Dossier Admin (ROLE_DOSSIER_ADMIN)](#role-dossier-admin-role_dossier_admin)
    - [Role Only View Access (ROLE_VIEW_ACCESS)](#role-only-view-access-role_view_access)
- [Details per test case](#details-per-test-case)

## Test cases Public Website

These test cases are used to test the OpenMinVWS website. The test cases are divided into three categories:

- Static pages
- Search and filter
- Dossier contents and relations

### Public Website

| Nr    | Breadcrumb                                        |                                                                           | Status             |
|-------|---------------------------------------------------|---------------------------------------------------------------------------|--------------------|
| P-001 | Home                                              | Homepage shows 5 recently published dossiers                              |                    |
| P-002 | Home                                              | Homepage shows categories (3 each)                                        |                    |
| P-003 | Home                                              | Homepage click on a category enables corresponding search filter checkbox |                    |
| P-004 | Home                                              | Homepage use search input field and navigate to the results               |                    |
| P-005 | Home :arrow_forward: Alle categoriëen             | Search                                                                    | :white_check_mark: |
| P-006 | Home :arrow_forward: Zoeken                       | Filter search results                                                     | :white_check_mark: |
| P-007 | Home :arrow_forward: Alle gepubliceerde besluiten | Search                                                                    | :white_check_mark: |

## Statussen

- draft
- stappen uitschrijven
- stappen automatiseren in RF
- automatiseren in de CI/CD pipeline

## Test cases Private Admin (Balie)

These test cases are used to test the OpenMinVWS Admin portal. The test cases are divided into three categories:

- Authentication and user management
- Dossiers and Cases (Zaken)
- History and logging

We create multiple test cases for each category and if needed we create test case variants per role (see [access-roles.md](access-roles.md) ).

### Private Admin (Balie)

#### Role Super Admin (ROLE_SUPER_ADMIN)

| Nr     | Title                      | URL                  | CI | TST | ACC |
|--------|----------------------------|----------------------|----|-----|-----|
| AA-001 | Login as a super admin     | /balie               |    |     |     |
| AA-002 | Show list all users        | /balie/gebruikers    |    |     |     |
| AA-003 | Create another super admin | /balie/gebruiker/new |    |     |     |

#### Role Global Admin (ROLE_GLOBAL_ADMIN)

| Nr     | Title                       | URL | CI | TST | ACC |
|--------|-----------------------------|-----|----|-----|-----|
| AB-001 | Login as a global admin     | ... |    |     |     |
| AB-002 | Create another global admin | ... |    |     |     |
| AB-003 | Change another global admin | ... |    |     |     |

#### Role Organisation Admin (ROLE_ORGANISATION_ADMIN)

| Nr     | Title                                       | URL | CI | TST | ACC |
|--------|---------------------------------------------|-----|----|-----|-----|
| AC-001 | Login as an organisation admin              | ... |    |     |     |
| AC-002 | Create another admin with this organisation | ... |    |     |     |

#### Role Dossier Admin (ROLE_DOSSIER_ADMIN)

| Nr     | Title                       | URL | CI | TST | ACC |
|--------|-----------------------------|-----|----|-----|-----|
| AD-001 | Login as an dossier admin   | ... |    |     |     |
| AD-002 | Open a dossier details page | ... |    |     |     |

#### Role Only View Access (ROLE_VIEW_ACCESS)

| Nr     | Title                                  | URL | CI | TST | ACC |
|--------|----------------------------------------|-----|----|-----|-----|
| AE-001 | Login as an user with view access only | ... |    |     |     |
| AE-002 | Open a dossier details page            | ... |    |     |     |

## Details per test case

- **AA-001:** Fill in Login form with e-mail and password :arrow_forward: Click button 'Inloggen' :arrow_forward: Add 2FA Code :arrow_forward: Click button 'Controleren'
- **AA-002:** Click in main menu on 'Toegangsbeheer' :arrow_forward: see list of users with title 'Alle gebruikers'
- **AA-003:** Click 'Toegangsbeheer' :arrow_forward: Click button 'Nieuwe gebruiker aanmaken' :arrow_forward: fill in form details (Voor- en achternaam / check role Super beheerder / E-mailadres) :arrow_forward: click button 'Account aanmaken'
