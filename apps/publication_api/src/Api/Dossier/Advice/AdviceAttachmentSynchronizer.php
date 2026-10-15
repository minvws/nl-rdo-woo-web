<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Symfony\Component\Validator\ConstraintViolationList;

use function array_filter;
use function count;
use function sprintf;

readonly class AdviceAttachmentSynchronizer
{
    public function __construct(
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
    ) {
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function create(Advice $advice, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->dossierAttachmentSynchronizer->create($advice, $attachmentRequestDtos);
        self::assertAtMostOneRequestForAdvice($advice->getAttachments()->getValues());

        return $attachmentEvents;
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(Advice $advice, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->dossierAttachmentSynchronizer->update($advice, $attachmentRequestDtos);
        self::assertAtMostOneRequestForAdvice($advice->getAttachments()->getValues());

        return $attachmentEvents;
    }

    /**
     * @param list<AdviceAttachment> $attachments
     */
    private static function assertAtMostOneRequestForAdvice(array $attachments): void
    {
        $attachmentType = AttachmentType::REQUEST_FOR_ADVICE;

        $requestsForAdvice = array_filter(
            $attachments,
            static fn (AdviceAttachment $attachment): bool => $attachment->getType() === $attachmentType,
        );

        if (count($requestsForAdvice) <= 1) {
            return;
        }

        throw new ValidationException(ConstraintViolationList::createFromMessage(sprintf(
            'dossier should have at most one attachment of type "%s"',
            $attachmentType->value,
        )));
    }
}
