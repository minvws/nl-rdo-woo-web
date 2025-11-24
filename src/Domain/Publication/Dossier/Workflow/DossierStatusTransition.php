<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

enum DossierStatusTransition: string
{
    case UPDATE_DETAILS = 'update_details';
    case UPDATE_DECISION = 'update_decision';
    case UPDATE_PRODUCTION_REPORT = 'update_production_report';
    case UPDATE_DOCUMENTS = 'update_documents';
    case SCHEDULE_PUBLISH = 'schedule_publish';
    case SCHEDULE_PUBLISH_AS_PREVIEW = 'schedule_publish_as_preview';
    case PUBLISH_AS_PREVIEW = 'publish_as_preview';
    case PUBLISH = 'publish';
    case DELETE = 'delete';
    case UPDATE_CONTENT = 'update_content';
    case UPDATE_MAIN_DOCUMENT = 'update_main_document';
    case DELETE_MAIN_DOCUMENT = 'delete_main_document';
    case UPDATE_ATTACHMENT = 'update_attachment';
    case DELETE_ATTACHMENT = 'delete_attachment';
}
