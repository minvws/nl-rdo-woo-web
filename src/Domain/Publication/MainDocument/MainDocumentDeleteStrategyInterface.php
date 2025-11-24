<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A main document delete strategy is called for an AbstractMainDocument that is being deleted based on autowiring by
 * the interface. If additional checks are needed you should implement something like an instanceof check and return
 * early when a main document is not supported.
 *
 * Can be implemented to handle side-effects of entity deletion, for instance cleaning up other data/artifacts related
 * to the main document entity. This way the DeleteMainDocumentHandler doesn't get too many responsibilities.
 *
 * Important: a strategy MUST NOT delete the main document entity itself or its related entities! This will be handled
 * after all delete strategies have been executed. Since these strategies are executed synchronously they should
 * dispatch async commands for (relatively) slow actions.
 */
#[AutoconfigureTag('woo_platform.publication.main_document_delete_strategy')]
interface MainDocumentDeleteStrategyInterface
{
    public function delete(AbstractMainDocument $mainDocument): void;
}
