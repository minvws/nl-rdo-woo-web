# Dossier types

<!-- TOC -->
- [Existing types](#existing-types)
- [Add a new dossier type with a feature flag](#add-a-new-dossier-type-with-a-feature-flag)
- [Defining a new dossier type](#defining-a-new-dossier-type)
- [Main document support](#main-document-support)
- [Attachment support](#attachment-support)
- [Admin pages](#admin-pages)
- [Public pages](#public-pages)
- [Ingest](#ingest)
- [Search support](#search-support)
- [Publication API](#publication-api)
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
- DraftDecision

## Add a new dossier type with a feature flag

The best way to develop a new dossier type is behind a feature flag that has the value `true` locally, so the type stays disabled in other environments until it is finished.

The feature flag check can be implemented in the `getSecurityExpression` method of the dossier type config: return `new Expression('false')` when the flag is disabled.

Making the publication type available only to super-admins (`return new Expression('is_granted("ROLE_SUPER_ADMIN")');`) instead of using a feature flag can have unpleasant consequences.
On the test environment it led to errors and other unwanted side effects when a super-admin created a dossier of a type that was only partially implemented.
Moreover, another publication type remained disabled for users on production for a very long time because we forgot to also enable the type for non-super-admins.

## Defining a new dossier type

Dossiers have generic properties and type-specific properties.
For instance a WooDecision supports documents while a Covenant doesn't. The entity implementation should reflect this.

In the examples below the name 'FooBar' is used for the new dossier type.

- Add a new case to the DossierType enum
  - Also add the ```isFooBar``` helper method.
- Define a namespace for the dossier type. In this example: `Shared\Domain\Publication\Dossier\Type\FooBar`.
- Create a Doctrine entity
  - This class is placed within the newly created namespace.
  - Each dossiertype should extend the AbstractDossier entity. See one of the existing implementations like ```AnnualReport``` in ```src/Domain/Publication/Dossier/Type/AnnualReport``` as an example.
  - [Single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/inheritance-mapping.html#single-table-inheritance) is used for dossier types.
  - All shared properties are defined in `AbstractDossier`. Only define properties and relationships that are unique to this type in the entity.
  - Add the new entity to the DiscriminatorMap attribute in ```AbstractDossier```
  - Also create a type-specific repository that extends `AbstractDossierRepository`, see ```AnnualReportRepository``` for an example.
  - Generate a database migration. This is only needed when adding custom fields to the entities.
- Create workflow definition
  - This class is placed within the newly created namespace.
  - See one of the existing dossier type namespaces for an example, `AnnualReportWorkflow.php` is probably a good starting point for most workflows.
  - Expose the workflow name as a `FOO_BAR_WORKFLOW_NAME` constant (value `foo_bar_workflow`) and the definition via a
    static `getConfiguration()` method, then register both in `config/packages/workflow.php`.
  - The new workflow name must also be added to `DossierWorkflowGuard::getSubscribedWorkflows()`.
  - You can generate a visual representation of the workflow definition, this can be very helpful in validating the definition:

    ```shell
    task shell
    bin/console --tenant=minvws workflow:dump foo_bar_workflow --dump-format=mermaid --env=dev
    ```

    The generated mermaid code can be visualized using for instance [mermaid.live](https://mermaid.live/).
- Create dossier type definition
  - The `DossierTypeConfigInterface` must be implemented, see the existing implementations for reference.
  - For the `getSteps` method you can use `StepDefinition::create($this, StepName::DETAILS)` for standard wizard steps. Only if a step needs custom behavior you need to implement the `StepDefinitionInterface` in a separate class.
  - Each step definition refers to route names, see the controller implementation docs below.
- Add support for the new type in the Diwoo sitemap by introducing a match case for the new dossier to `src/Domain/WooIndex/Producer/Mapper/DiWooDocumentMapper.php` method `mapDossierTypeToInformatieCategorie`
- Add support for the new type in `src/Domain/Publication/Dossier/ViewModel/DossierPathHelper.php` by added a new match case in the method `getDetailsPath`
- Add support for the new type in `DossierVoter::supports` by adding a new match case. This assumes standard dossier access voting.
  - If you need custom / complex dossier voting you should not add the new type here, but instead implement a custom voter. See `WooDecisionVoter` for reference.
- There are generic delete strategies in place to handle dossier removal. They remove the dossier from elasticsearch, remove the main document (when present) and remove attachments (when present).
  - If you introduce additional related entities and/or files you should implement an additional delete strategy. See ``WooDecisionDeleteStrategy`` as an example.

## Main document support

Optionally the new dossier type could support a main document. In that case these additional steps are needed:

- Create a main document entity, see `AnnualReportMainDocument` as an example. This also requires a repository.
- Add the new MainDocument class to the discriminator mapping in `AbstractMainDocument`
- The entity class should implement the `EntityWithMainDocument` interface, see the existing implementations for reference.
  - The `HasMainDocument` trait can be used to simplify this.
  - You should define the doctrine relationship and initialize the collection in the constructor.
- Implement admin API endpoints (DTO, provider and processor) for the editor in the admin app. Use an existing implementation as reference, for instance `apps/admin/src/Api/Admin/AnnualReportMainDocument`.
  - Also include test coverage, see the `Admin\Tests\Integration\Api\Admin\AnnualReportMainDocumentTest` as an example.

## Attachment support

Optionally the new dossier type could support attachments. In that case these additional steps are needed:

- Create an attachment entity, see `AnnualReportAttachment` as an example. This also requires a repository.
- Add the new attachment class to the discriminator mapping in `AbstractAttachment`
- The entity class should implement the `EntityWithAttachments` interface, see the existing implementations for reference.
  - The `HasAttachments` trait can be used to simplify this.
  - You should define the doctrine relationship and initialize the collection in the constructor.
- The **attachment** entity defines which attachment types are allowed, through the static `getAllowedTypes` method
  (see `AttachmentAndMainDocumentEntityTrait` for the default). If a new attachment type is needed:
  - Add a case to the `AttachmentType` enum (the value is a TOOI identifier) and add it to `AttachmentTypeFactory`.
  - Add a translation for the new type to `translations/attachment+intl-icu.nl.yaml`.
- If the dossier type requires specific attachments you can add a validation constraint on the entity, see `HasOneAttachmentOfTypes` as an example. Don't forget the messages in `translations/validators.nl.yaml` and `translations/validators.en.yaml`.
- Implement admin API endpoints (DTO, provider and processor) for the editor in the admin app. Use an existing implementation as reference, for instance `apps/admin/src/Api/Admin/AnnualReportAttachment`.
  - Also include test coverage, see the `Admin\Tests\Integration\Api\Admin\AnnualReportAttachmentTest` as an example.

## Admin pages

The admin is also referred to as 'balie'. This includes the controllers + actions, forms, routes + urls and templates. It is the largest part of a dossier type implementation.

The following naming convention is used:

1. `admin/dossier` folder/namespace
2. dossier type
3. step
4. edit mode (`edit` or `concept`)

For the 'details' step of the 'FooBar' dossier type this results in the following paths / namespaces:

- templates
  - `templates/admin/dossier/foo-bar/details/concept.html.twig`
  - `templates/admin/dossier/foo-bar/details/edit.html.twig`
- routes
  - `app_admin_dossier_foobar_details_concept`
  - `app_admin_dossier_foobar_details_edit`
- urls
  - `/balie/dossier/foo-bar/details/concept/{documentPrefix}/{dossierNumber}`
  - `/balie/dossier/foo-bar/details/edit/{documentPrefix}/{dossierNumber}`
- controllers/actions
  - `Shared\Controller\Admin\Dossier\FooBar\DetailsStepController::concept`
  - `Shared\Controller\Admin\Dossier\FooBar\DetailsStepController::edit`
  - If the step needs a lot of actions or has many differences between edit and concept mode it can be split up into separate controllers:
    - `Shared\Controller\Admin\Dossier\FooBar\DetailsConceptStepController::concept`
    - `Shared\Controller\Admin\Dossier\FooBar\DetailsEditStepController::edit`
- forms
  - In many cases a single form can be used for both edit modes: `Shared\Form\Dossier\FooBar\DetailsType`
  - If needed separate forms per edit mode can be defined: `Shared\Form\Dossier\FooBar\DetailsConceptType`

For multi-word type names the template folder uses kebab-case (`templates/admin/dossier/annual-report/...`) while route names use the lowercased name without separator (`app_admin_dossier_annualreport_details_concept`).

Besides the wizard step templates you also need a dossier overview page (`view.html.twig`) and a publication confirmation page (`publication-confirmation.html.twig`) in the same template folder.
These are rendered by the generic `DossierController` based on the dossier type value.

Use the existing implementations as a starting point / reference, `AnnualReport` is a compact example with details, content and publication steps.

## Public pages

- Add a viewmodel and factory in the `Shared\Domain\Publication\Dossier\Type\FooBar\ViewModel` namespace.
- Create a new namespace `Shared\Controller\Public\Dossier\FooBar` for the new type
- Add a new `FooBarController` class in this namespace, based on for instance `src/Controller/Public/Dossier/AnnualReport/AnnualReportController.php`.
  This defines the detail page route (referenced from `DossierPathHelper`, see above) and routes for the main document and attachment detail pages (when applicable).
- Add the public templates in `templates/public/dossier/foo-bar/` (`details.html.twig`, and `document.html.twig` / `attachment.html.twig` when applicable). See the existing templates in `templates/public/dossier/annual-report` for reference.

## Ingest

Ingesting is the process of data (re-)ingestion into the system.
Not just for the dossier entity itself but also all related data (relationships, files etcetera).
Some examples of the resulting actions: indexing into ElasticSearch, generating thumbnails, executing OCR.

Ingest must be able to completely restore or renew all data for the public website based on the database and file storage.
It can be executed for all dossiers, or just for a single dossier.

All dossier types automatically use the ``DefaultDossierIngestStrategy`` which is based on data available in ``AbstractDossier``.
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
- Add a new namespace for search result mapping: `Shared\Domain\Search\Result\Dossier\FooBar`
  - Create a search result viewmodel that extends ``AbstractDossierTypeSearchResult`` (which implements the marker interface ``DossierTypeSearchResultInterface``). See ``AnnualReportSearchResult`` for an example.
  - Implement ``ProvidesDossierTypeSearchResultInterface`` in the repository of the dossier type. The ``getSearchResultViewModel`` method receives an ``ApplicationId``,
    use ``$applicationId->getAccessibleDossierStatuses()`` to filter on the dossier statuses that are accessible for that application (public frontend versus admin). See ``AnnualReportRepository`` for an example.
  - Implement ``SearchResultMapperInterface``. In most cases you can use ``DossierSearchResultBaseMapper`` to make this easier, see ``AnnualReportSearchResultMapper`` for an example.
- Create a template at ``templates/public/search/entries/[ELASTICDOCUMENTTYPE-VALUE].html.twig``. If the type has a main document also create ``templates/public/search/entries/[MAIN-DOCUMENT-TYPE-VALUE].html.twig``.

## Publication API

The publication API is a separate application in `apps/publication_api` (namespace `PublicationApi\`), built with API Platform.
It allows external parties to manage publications, it is enabled with the `HAS_FEATURE_PUBLICATION_V1_API` feature flag.
To add support for a new dossier type:

- Create a new namespace `PublicationApi\Api\Dossier\FooBar` containing:
  - `FooBarResource`: the API Platform resource defining the operations, typically:
    - `Get` and `Put` on `/organisation/{organisationId}/dossiers/foo-bar/external/{dossierExternalId}`
    - `GetCollection` on `/organisation/{organisationId}/dossiers/foo-bar` with cursor-based pagination (`CursorPage`)
  - `FooBarRequestDto` (extends `AbstractDossierRequestDto`) and `FooBarResponseDto`
  - Request/response DTOs for the main document and attachments (when applicable), for example `FooBarMainDocumentRequestDto` / `FooBarMainDocumentResponseDto`
  - `FooBarProvider`: implements `ProviderInterface`, resolves the organisation with `OrganisationResolver` and loads the dossier(s) from the repository. Collections are paginated using `CursorPageFactory`.
  - `FooBarProcessor`: implements the processor for the `Put` operation (create or update)
  - `FooBarMapper` (and main document / attachment mappers) to map between entities and DTOs. For main document response DTOs use `MainDocumentResponseDtoFactory`,
    it has a `fromEntityWithoutGrounds` variant for dossier types whose documents may not be redacted (and thus have no grounds property).
- Add file upload endpoints in `PublicationApi\Api\Dossier\FooBar\Uploads\MainDocument` and `PublicationApi\Api\Dossier\FooBar\Uploads\Attachment` (when applicable). Each consists of:
  - An upload resource defining a `Put` operation with `application/octet-stream` input, for example on `/organisation/{organisationId}/dossiers/foo-bar/external/{dossierExternalId}/uploads/main-document`
  - A request DTO, a request DTO factory (used as controller) and a processor
- Add test coverage:
  - An integration test covering the full publication flow, see `PublicationApi\Tests\Integration\Api\Dossier\AnnualReport\AnnualReportPublicationV1Test` as an example.
  - Integration and unit tests for the upload endpoints, see the `AnnualReportUploadMainDocumentTest` and `AnnualReportUploadMainDocumentProcessorTest` as examples.
- Update the Bruno collection: add a folder `docs/bruno-collection/FooBar` with a `.bru` request file per endpoint (including a `folder.bru`). See `docs/bruno-collection/AnnualReport` as an example and `docs/bruno-collection/README.md` for how to use the collection.

See `apps/publication_api/src/Api/Dossier/AnnualReport` for a complete reference implementation.

## Translations

Add translation keys for the new type to `translations/messages+intl-icu.nl.yaml`. Use an existing group of translation keys as a starting point, for instance the keys between these comments:

```yaml
#START other publication
#END other publication
```

This example contains translations for attachments and a main document too, remove them if the new type does not have those relations.
