<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

/**
 * An attachment delete strategy is called for an AbstractAttachment that is being deleted based on autowiring by the
 * interface. If additional checks are needed you should implement something like an instanceof check and return early
 * when an attachment is not supported.
 *
 * Can be implemented to handle side-effects of entity deletion, for instance cleaning up other data/artifacts related
 * to the attachment entity. This way the DeleteAttachmentHandler doesn't get too many responsibilities.
 *
 * Important: a strategy MUST NOT delete the attachment entity itself or its related entities! This will be handled
 * after all delete strategies have been executed. Since these strategies are executed synchronously they should
 * dispatch async commands for (relatively) slow actions.
 */
interface AttachmentDeleteStrategyInterface
{
    public function delete(AbstractAttachment $attachment): void;
}
