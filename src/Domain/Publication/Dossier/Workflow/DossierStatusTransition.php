<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Workflow;

enum DossierStatusTransition: string
{
    case UPDATE_DETAILS = 'update_details';
    case UPDATE_DECISION_DOCUMENT = 'update_decision_document';
    case UPDATE_DECISION = 'update_decision';
    case UPDATE_INVENTORY = 'update_inventory';
    case UPDATE_DOCUMENTS = 'update_documents';
    case SCHEDULE = 'schedule';
    case PUBLISH_AS_PREVIEW = 'publish_as_preview';
    case PUBLISH = 'publish';
    case DELETE = 'delete';
    case UPDATE_CONTENT = 'update_content';
    case DELETE_COVENANT_DOCUMENT = 'delete_covenant_document';
    case DELETE_COVENANT_ATTACHMENT = 'delete_covenant_attachment';
    case DELETE_DECISION_ATTACHMENT = 'delete_decision_attachment';
}
