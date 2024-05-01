# Dossier types

<!-- TOC -->
- [Dossier types](#dossier-types)
  - [Defining a new dossier type](#defining-a-new-dossier-type)
    - [Add a new case to the DossierType enum](#add-a-new-case-to-the-dossiertype-enum)
    - [Translations](#translations)
    - [Define a namespace for the dossier type](#define-a-namespace-for-the-dossier-type)
    - [Create a Doctrine entity](#create-a-doctrine-entity)
    - [Create workflow definition](#create-workflow-definition)
    - [Create dossier type definition](#create-dossier-type-definition)
    - [Implement admin (balie) actions](#implement-admin-balie-actions)
<!-- TOC -->

There are multiple types of dossiers:

- WooDecision
- Covenant

Dossiers have generic properties and type-specific properties. For instance a WooDecision supports documents while a Covenant doesn't.

## Defining a new dossier type

In the examples below the name 'foo' is used for the new dossier type.

### Add a new case to the DossierType enum

Also add the ```isFoo``` helper method.

### Translations

Add the following translation keys:

```yaml
dossier.type.foo: 'Foo dossier'  #Used as the name the dossier type, for instance in the admin dossier overview
dossier.type.foo.description: 'A foo dossier contains (...)'  #Used to describe the type of the entity in more detail
```

### Define a namespace for the dossier type

In this case: `App\Domain\Publication\Dossier\Type\Foo`.

### Create a Doctrine entity

- This class is placed within the newly created namespace.
- Each dossiertype should extend the AbstractDossier entity. See one of the existing implementations like ```WooDecision``` in ```src/Domain/Publication/Dossier/Type/WooDecision``` as an example.
- Define properties and relationships that are unique to this type in the entity and generate a migration.
- Add the new entity to the DiscriminatorMap attribute in ```AbstractDossier```
- Optional: declare a type-specific repository class in the new entity class, see ```WooDecision``` for an example. Only if you need special queries for this type.

### Create workflow definition

- This class is placed within the newly created namespace.
- See one of the existing dossier type namespaces for an example, `CovenantWorkflow.php` is probably a good starting point for most workflows.
- The new definition must be added to `config/packages/workflow.php`
- You can generate a visual representation of the workflow definition, this can be very helpful in validating the definition:

  ```shell
  task shell
  bin/console  workflow:dump foo_workflow --dump-format=mermaid --env=dev
  ```
  
  The generated mermaid code can be visualized using for instance [mermaid.live](https://mermaid.live/).

### Create dossier type definition

- The `DossierTypeConfigInterface` must be implemented.
- See the existing implementations for reference.
- For the `getSteps` method you need to implement the `StepDefinitionInterface` in a separate class for each wizard step.
- Each step definition refers to route names, see the controller implementation docs below.

### Implement admin (balie) actions

This includes the controllers + actions, forms, routes + urls and templates. This is by far the largest part of a dossier type implementation.

The following naming convention is used:

1. `admin/dossier` folder/namespace
2. dossier type
3. step
4. edit mode (`edit` or `concept`)

For the 'details' step of the 'foo' dossier type this results in the following paths / namespaces:

- templates
  - `templates/admin/dossier/foo/details/concept.html.twig`
  - `templates/admin/dossier/foo/details/edit.html.twig`
- routes
  - `app_admin_dossier_foo_details_concept`
  - `app_admin_dossier_foo_details_edit`
- urls
  - `/balie/dossier/foo/details/concept/{prefix}/{dossierId}`
  - `/balie/dossier/foo/details/edit/{prefix}/{dossierId}`
- controllers/actions
  - `App\Controller\Admin\Dossier\Foo\DetailsStepController::concept`
  - `App\Controller\Admin\Dossier\Foo\DetailsStepController::edit`
  - If the step needs a lot of actions or has many differences between edit and concept mode it can be split up into separate controllers:
    - `App\Controller\Admin\Dossier\Foo\DetailsConceptStepController::concept`
    - `App\Controller\Admin\Dossier\Foo\DetailsEditStepController::edit`
- forms
  - In many cases a single form can be used for both edit modes: `App\Form\Dossier\Foo\DetailsType`
  - If needed separate forms per edit mode can be defined: `App\Form\Dossier\Foo\DetailsConceptType`
