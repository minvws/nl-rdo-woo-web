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

#### Update match cases using this enum

In ```src/Controller/Admin/Dossier/DossierController.php``` there are two 2 match statements using the DossierType cases.
Add the new case in both.

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
- Define properties and relationships that are unique to this type in the entity.
- Add the new entity to the DiscriminatorMap attribute in ```AbstractDossier```
- Also create a type-specific repository class in the new entity class, see ```CovenantRepository``` for an example.
- Usually a dossier type also has relationships to sub-entities, at least attachments. Create these entities and a repositoy for each.
- Generate a database migration

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

### Dossier delete strategy

There are generic delete strategies in place that remove the dossier from elasticsearch, remove the main document (when present) and remove attachments (when present).
If you introduce additional related entities and/or files you should implement an additional delete strategy. See ``WooDecisionDeleteStrategy`` as an example.

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

### Ingest

Ingesting is the process of data (re-)ingestion into the system.
Not just for the dossier entity itself but also all related data (relationships, files etcetera).
Some examples of the resulting actions: indexing into ElasticSearch, generating thumbnails, executing OCR.

Ingest must be able to completely restore or renew all data for the public website based on the database and file storage.
It can be executed for all dossiers, or just for a single dossier.

All dossier types automatically use the ``DefaultIngester`` which is based on data available in ``AbstractDossier``.
For most dossiertypes this should suffice, but if you need to ingest additional relationships and/or related files that are specific to the dossier type you should implement the ``DossierIngestStrategyInterface`` and add it to the mapping in ``DossierIngester``.
See ``WooDecisionIngestStrategy`` for an example.

### Search index mapper

- Add a new case to the ``ElasticDocumentType`` enum. Also update the ``getMainTypes`` method.
- Implement ``ElasticDossierMapperInterface`` if needed. All dossier types automatically use the ``DefaultDossierMapper`` which indexes common properties from ``AbstractDossier``.
If you need to map additional data into ElasticSearch that is specific to the dossier type you should implement a custom mapper. In that case you probably also need to add new fields to the ES schema.
See ``WooDecisionMapper`` for an example. If you implement the ``ElasticDossierMapperInterface`` your mapper will be autowired with a higher priority than the default mapper.

### Search results

- Create a search result viewmodel that implements the marker interface ``DossierTypeSearchResultInterface``.
- Implement ``ProvidesDossierTypeSearchResultInterface`` in the repository of the dossier type, this should return the new search result viewmodel.
- Implement ``DossierTypeSearchResultMapperInterface``. In most cases you can use ``DossierTypeSearchResultMapper`` as a base to make this easier.
- Add a new ``match`` case to ``ResultFactory::map``
- Create a template at ``templates/search/entries/[ELASTICDOCUMENTTYPE-VALUE].html.twig``

### Implement public website controller actions and templates

- Implement a controller in ``src/Controller/Public/Dossier/[DOSSIERTYPE]/[DOSSIERTYPE]Controller.php``
- Use one of the existing implementations as an example
- Add templates for all new actions
