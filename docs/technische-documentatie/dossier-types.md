# Dossier types

<!-- TOC -->
- [Dossier types](#dossier-types)
  - [Existing types](#existing-types)
  - [Defining a new dossier type](#defining-a-new-dossier-type)
  - [Main document support](#main-document-support)
  - [Attachment support](#attachment-support)
  - [Admin pages](#admin-pages)
  - [Public pages](#public-pages)
  - [Ingest](#ingest)
  - [Search support](#search-support)
  - [Translations](#translations)
<!-- TOC -->

## Existing types

- WooDecision
- Covenant
- AnnualReport
- InvestigationReport
- Disposition
- ComplaintJudgement
- OtherPublication
- Advice
- RequestForAdvice

## Defining a new dossier type

Dossiers have generic properties and type-specific properties.
For instance a WooDecision supports documents while a Covenant doesn't. The entity implementation should reflect this.

In the examples below the name 'foo' is used for the new dossier type.

- Add a new case to the DossierType enum
  - Also add the ```isFoo``` helper method.
- Define a namespace for the dossier type. In this example: `App\Domain\Publication\Dossier\Type\Foo`.
- Create a Doctrine entity
  - This class is placed within the newly created namespace.
  - Each dossiertype should extend the AbstractDossier entity. See one of the existing implementations like ```AnnualReport``` in ```src/Domain/Publication/Dossier/Type/AnnualReport``` as an example.
  - [Single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/inheritance-mapping.html#single-table-inheritance) is used for dossier types.
  - All shared properties are defined in `AbstractDossier`. Only define properties and relationships that are unique to this type in the entity.
  - Add the new entity to the DiscriminatorMap attribute in ```AbstractDossier```
  - Also create a type-specific repository class in the new entity class, see ```AnnualReportRepository``` for an example.
  - Generate a database migration. This is only needed when adding custom fields to the entities.
- Create workflow definition
  - This class is placed within the newly created namespace.
  - See one of the existing dossier type namespaces for an example, `AnnualReportWorkflow.php` is probably a good starting point for most workflows.
  - The new definition must be added to `config/packages/workflow.php`
  - You can generate a visual representation of the workflow definition, this can be very helpful in validating the definition:

    ```shell
    task shell
    bin/console  workflow:dump foo_workflow --dump-format=mermaid --env=dev
    ```

    The generated mermaid code can be visualized using for instance [mermaid.live](https://mermaid.live/).
- Create dossier type definition
  - The `DossierTypeConfigInterface` must be implemented.
  - See the existing implementations for reference.
  - For the `getSteps` method you need to implement the `StepDefinitionInterface` in a separate class for each wizard step.
  - Each step definition refers to route names, see the controller implementation docs below.
  - The `getSecurityExpression` can be used to control access to the type. If no additional checks are needed just return `null`, otherwise use a [Symfony security expression](https://symfony.com/doc/current/security/expressions.html).
  - This can also be used as a kind of feature flag, limiting access to (new) types to super-admins with this code:

    ```php
    return new Expression('is_granted("ROLE_SUPER_ADMIN")');
    ```

- Add support for the new type in the Diwoo sitemap by introducing a match case for the new dossier to `src/Domain/WooIndex/Producer/DiWooDocumentFactory.php` method `mapDossierTypeToInformatieCategorie`
- Add support for the new type in `src/Domain/Publication/Dossier/ViewModel/DossierPathHelper.php` by added a new match case in the method `getDetailsPath`
- Add support for the new type in `DossierVoter::supports` by adding a new match case. This assumes standard dossier access voting.
  - If you need custom / complex dossier voting you should not add the new type here, but instead implement a custom voter. See `WooDecisionVoter` for reference.
- There are generic delete strategies in place to handle dossier removal. They remove the dossier from elasticsearch, remove the main document (when present) and remove attachments (when present).
  - If you introduce additional related entities and/or files you should implement an additional delete strategy. See ``WooDecisionDeleteStrategy`` as an example.

## Main document support

Optionally the new dossier type could support a main document. In that case these additional steps are needed:

- Create an attachment entity, see `AnnualReportMainDocument` as an example. This also requires a repository.
- Add the new MainDocument class to the discriminator mapping in `AbstractMainDocument`
- The entity class should implement the `EntityWithMainDocument` interface, see the existing implementations for reference.
  - The `hasMainDocument` trait can be used to simplify this.
  - You should define the doctrine relationship and initialize the collection in the constructor.
- Implement admin API endpoints for the editor. Use an existing implementation as reference, for instance `App\Api\Admin\AnnualReportMainDocument```
  - Also include test coverage, see the `App\Tests\Integration\Api\Admin\AnnualReportMainDocumentTest` as an example.

## Attachment support

Optionally the new dossier type could support attachments. In that case these additional steps are needed:

- Create an attachment entity, see `AnnualReportAttachment` as an example. This also requires a repository.
- Add the new attachment class to the discriminator mapping in `AbstractAttachment`
- The entity class should implement the `EntityWithAttachments` interface, see the existing implementations for reference.
  - The `hasAttachments` trait can be used to simplify this.
  - You should define the doctrine relationship and initialize the collection in the constructor.
- Implement admin API endpoints for the editor. Use an existing implementation as reference, for instance `App\Api\Admin\AnnualReportAttachment```
  - Also include test coverage, see the `App\Tests\Integration\Api\Admin\AnnualReportAttachmentTest` as an example.

## Admin pages

The admin is also referred to as 'balie'. This includes the controllers + actions, forms, routes + urls and templates. It is the largest part of a dossier type implementation.

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

Use the existing implementations as a starting point / reference.

## Public pages

- Add a viewmodel and factory in the `App\Domain\Publication\Dossier\Type\Foo\ViewModel` namespace.
- Create a new namespace `App\Controller\Public\Dossier\OtherPublication\Foo` for the new type
- Add a new `FooController` class in this namespace, based on for instance `src/Controller/Public/Dossier/AnnualReport/AnnualReportController.php`
- Add the public templates. See the existing templates in `templates/annualreport` for reference.

## Ingest

Ingesting is the process of data (re-)ingestion into the system.
Not just for the dossier entity itself but also all related data (relationships, files etcetera).
Some examples of the resulting actions: indexing into ElasticSearch, generating thumbnails, executing OCR.

Ingest must be able to completely restore or renew all data for the public website based on the database and file storage.
It can be executed for all dossiers, or just for a single dossier.

All dossier types automatically use the ``DefaultIngester`` which is based on data available in ``AbstractDossier``.
For most dossiertypes this should suffice, but if you need to ingest additional relationships and/or related files that are specific to the dossier type you should implement the ``DossierIngestStrategyInterface`` and add it to the mapping in ``DossierIngester``.
See ``WooDecisionIngestStrategy`` for an example.

## Search support

- Add new cases to `ElasticDocumentType`. This enum defines all ElasticSearch document types.
  - One case is at least needed for the publication itself
  - If the type has a main document this also needs a new case.
  - For attachments no new case is needed.
  - Extend the `fromEntity` and `fromEntityClass` methods with matches for the new cases that have been added.
  - Add the new cases to `getMainTypes`, `getSubTypes` and `getMainDocumentTypes` (where applicable)
- Implement ``ElasticDossierMapperInterface`` if needed. All dossier types automatically use the ``DefaultDossierMapper`` which indexes common properties from ``AbstractDossier``.
  If you need to map additional data into ElasticSearch that is specific to the dossier type you should implement a custom mapper. In that case you probably also need to add new fields to the ES schema.
  See ``WooDecisionMapper`` for an example. If you implement the ``ElasticDossierMapperInterface`` your mapper will be autowired with a higher priority than the default mapper.
- Add a new namespace for search result mapping: `App\Domain\Search\Result\Dossier\Foo`
  - Create a search result viewmodel that implements the marker interface ``DossierTypeSearchResultInterface``.
  - Implement ``ProvidesDossierTypeSearchResultInterface`` in the repository of the dossier type, this should return the new search result viewmodel.
  - Implement ``DossierTypeSearchResultMapperInterface``. In most cases you can use ``DossierTypeSearchResultMapper`` as a base to make this easier.
- Create a template at ``templates/public/search/entries/[ELASTICDOCUMENTTYPE-VALUE].html.twig``

## Translations

Add translation keys for the new type to `translations/messages+intl-icu.nl.yaml`. Use an existing group of translation keys as a starting point, for instance the keys between these comments:

```yaml
#START other publication
#END other publication
```

This example contains translations for attachments and a main document too, remove them if the new type does not have those relations.
